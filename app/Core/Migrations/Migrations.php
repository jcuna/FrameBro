<?php
/**
 * Author: Jon Garcia
 * Date: 5/29/16
 */

namespace App\Core\Migrations;

use App\Core\Db\DatabaseManager;
use App\Core\Interfaces\DatabaseAccessInterface;
use App\Core\Db\Database;
use App\Core\EventReceiver;
use App\Core\Exceptions\ModelException;
use App\Core\Db\Statement;

abstract class Migrations implements DatabaseAccessInterface
{
    /**
     * The connection to use
     *
     * @var DatabaseManager
     */
    private $connection;

    /**
     * Holds table name
     *
     * @var string
     */
    private $table;

    /**
     * Holds a sql statement
     *
     * @var \PDOStatement
     */
    private $query;

    /**
     * Contains errors form sql.
     */
    public $SQLError;

    /**
     * Time it took every query to execute in milliseconds
     *
     * @var array
     */
    private $elapsedTime = [];

    /**
     * Some errors arrive on update because the update was applied.
     * We verify that that's why these errors where
     * thrown and will catch them.
     *
     * @var array
     */
    private $acceptedErrors = [

        [ "error" => "Duplicate column name"],
        [ "error" => "Unknown column", "handler" => 'handleUnknownColumn']
    ];

    /**
     * Migrations constructor.
     * @param bool $down
     */
    public function __construct(bool $down = false)
    {
        EventReceiver::listenTo('sql', function($sql, $elapsed) {
            $this->elapsedTime[] = $elapsed;
        });

        $this->connection = $this->getConnection();

        if ($down) {
            $this->down();
        } else {
            $this->up();
        }
    }

    /**
     * @return DatabaseManager
     */
    public function getConnection(): DatabaseManager
    {
        return Database::getConnection($this->connection);
    }

    /**
     * @return Statement
     */
    public function getStatement(): Statement
    {
        return Statement::getStatement($this->table);
    }

    /**
     * @param $tableName
     * @param \Closure $closure
     * @throws \Exception
     */
    public function create(string $tableName, \Closure $closure)
    {
        $this->table = $tableName;

        $table = new Table($this->table, $this->getStatement());

        $closure($table);

        if (!$table->stm->hasPrimary()) {
            $table->incremental('id')->unsigned();
        }

        $this->prepareExecution($table);
    }

    /**
     * @param $tableName
     * @param \Closure $closure
     * @throws \Exception
     */
    public function update(string $tableName, \Closure $closure)
    {
        $this->table = $tableName;

        $stm = $this->getStatement();

        $stm->setAlterStatements();

        $table = new Table($tableName, $stm, true);

        $table->alter();

        $closure($table);

        $this->prepareExecution($table);
    }

    /**
     * @param $tableName
     */
    protected function drop(string $tableName)
    {
        $this->table = $tableName;

        $table = new Table($tableName, $this->getStatement(), true);

        $table->stm->drop();

        $this->prepareExecution($table);
    }

    /**
     * @param Table $table
     * @throws \Exception
     */
    public function prepareExecution(Table $table)
    {
        $this->connection->beginTransaction();

        try {
            $this->runQueries($this->connection, $table);
            $this->connection->commit();

        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @param \PDO $conn
     * @param Table $table
     * @throws ModelException
     */
    public function runQueries(\PDO $conn = null, Table $table)
    {
        $this->query = $table->getQuery();

        $conn->exec($this->query);

        $this->SQLError = $conn->errorInfo();

        $this->checkForErrors($table);
    }

    /**
     * @param Table $table
     * @throws ModelException
     */
    private function checkForErrors(Table $table)
    {
        if (isset($this->SQLError[0]) && $this->SQLError[0] !== "00000") {
            if ($this->isActualError($this->SQLError[2], $table)) {
                throw new ModelException($this->SQLError[2], $this->SQLError[1]);
            }
        }
    }

    /**
     * @param $errorMessage
     * @param Table $table
     * @return bool
     */
    public function isActualError(string $errorMessage, Table $table): bool
    {
        foreach ($this->acceptedErrors as $error) {

            if (strpos($errorMessage, $error['error']) === 0) {
                if (isset($error['handler'])) {
                    $method = $error['handler'];
                    return $this->$method($errorMessage, $table);
                }
                return false;
            }
        }

        return true;
    }

    /**
     * @param $message
     * @param Table $table
     * @return bool
     */
    public function handleUnknownColumn(string $message, Table $table): bool
    {
        foreach ($table->getRenamedColumns() as $column) {
            if (strpos($message, $column) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return float
     */
    public function getElapsedTimeSum()
    {
        return round(array_sum($this->elapsedTime), 2);
    }

    /**
     * @return void
     */
    abstract protected function up();

    /**
     * @return void
     */
    abstract protected function down();

}