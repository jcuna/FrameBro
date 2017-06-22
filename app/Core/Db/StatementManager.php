<?php
/**
 * Author: Jon Garcia.
 * Date: 6/18/16
 * Time: 11:55 AM
 */

namespace App\Core\Db;

use App\Core\EventReceiver;

class StatementManager
{

    /**
     * @var \PDOStatement
     */
    private $PDOStatement;

    /**
     * StatementManager constructor.
     * @param \PDOStatement $pdo
     */
    public function __construct(\PDOStatement $pdo)
    {
        $this->PDOStatement = $pdo;

    }

    /**
     * @param $bindings
     * @return bool
     */
    public function execute($bindings)
    {
        $query = $this->PDOStatement->queryString;

        $start = microtime(true);

        $result = $this->PDOStatement->execute($bindings);

        $elapsed = getExecutionTime($start);

        if (EventReceiver::listeningTo('sql')) {
            EventReceiver::sendEvent('sql', [$query, $elapsed, $bindings]);
        }

        return $result;
    }

    /**
     * @param $name
     * @param $arguments
     * @return \PDOStatement
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->PDOStatement, $name], $arguments);
    }

}