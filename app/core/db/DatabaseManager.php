<?php
/**
 * Author: Jon Garcia.
 * Date: 6/2/16
 * Time: 6:56 PM
 */

namespace App\Core\Db;

use App\Core\EventReceiver;

class DatabaseManager extends \PDO
{

    /**
     * The parent PDO 
     * 
     * @var \PDO
     */
    private $PDO;

    /**
     * DatabaseManager constructor.
     * @param $dsn
     * @param $username
     * @param $password
     * @param $options
     */
    public function __construct($dsn, $username, $password, $options)
    {
        $this->PDO = parent::__construct($dsn, $username, $password, $options);
    }

    /**
     * @param string $statement
     * @param $driver_options
     * @return StatementManager
     */
    public function prepare($statement, $driver_options = [])
    {
        $PDOStatement = parent::prepare($statement, $driver_options);
        
        return new StatementManager($PDOStatement);
    }

    /**
     * @param string $statement
     * @return int
     */
    public function exec($statement)
    {
        $start = microtime(true);

        $result = parent::exec($statement);

        $elapsed = getExecutionTime($start);

        if (EventReceiver::listeningTo('sql')) {
            EventReceiver::sendEvent('sql', [$statement, $elapsed]);
        }

        return $result;
    }

    /**
     * @param string $statement
     * @param int $mode
     * @param null $arg3
     * @return \PDOStatement
     */
    public function query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null)
    {
        $start = microtime(true);

        $result = parent::query($statement, $mode, $arg3);

        $elapsed = getExecutionTime($start);

        if (EventReceiver::listeningTo('sql')) {
            EventReceiver::sendEvent('sql', [$statement, $elapsed]);
        }

        return $result;
    }

    /**
     * Return PDO connection 
     * 
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->PDO;
    }
}