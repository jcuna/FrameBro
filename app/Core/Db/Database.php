<?php

/**
 *
 * Author: Jon Garcia
 *
 * Class Database
 *
 * Creates a PDO database connection. This connection will be passed into the models
 *
 */
namespace App\Core\Db;

use App\Core\Exceptions\AppException;
use App;

/**
 * Class Database
 * @package App\Core
 */
class Database
{
    /**
     * Holds the name of the current connection being requested.
     *
     * @var string
     */
    private static $connectionName;

    /**
     * Holds singleton of PDO connections in associative array.
     *
     * @var \PDO
     */
    private static $connections = [];

    /**
     * @var array
     */
    private static $settings = [];

    /**
     * Gets the requested connection by name.
     *
     * @return \PDO
     * @throws AppException
     */
    private static function newConnection()
    {
        self::configure();
        $errMode = self::getErrorMode();

        $dsn = self::getDSN();

        /**
         * set the (optional) options of the PDO connection. in this case, we set the fetch mode to
         * "objects".
         * @see http://www.php.net/manual/en/pdostatement.fetch.php
         */
        $options = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
            \PDO::ATTR_ERRMODE => $errMode
        ];

        /**
         * Generate a database connection, using the PDO connector
         * @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
         * Also important: We include the charset, as leaving it out seems to be a security issue:
         * @see http://wiki.hashphp.org/PDO_Tutorial_for_MySQL_Developers#Connecting_to_MySQL says:
         * "Adding the charset to the DSN is very important for security reasons,
         * most examples you'll see around leave it out. MAKE SURE TO INCLUDE THE CHARSET!"
         */
        return new DatabaseManager($dsn, App::env('DB_USER'), App::env('DB_PASSWORD'), $options);
    }

    /**
     * gets the PDO connection.
     *
     * @return DatabaseManager
     */
    public static function getConnection($connectionName = null)
    {
        //if connection name passed in is null
        if (is_null($connectionName)) {
            $connectionName = 'default';
        }

        if (isset(self::$connections[$connectionName])) {
            return self::$connections[$connectionName];
        } else {
            self::$connectionName = $connectionName;
            self::$connections[$connectionName] = self::newConnection();
            return self::$connections[$connectionName];
        }
    }

    /**
     * configure database connections
     *
     * @throws AppException
     */
    private static function configure()
    {
        //Get connection configuration
        $connections = App::getSettings("connections");

        if (self::$connectionName === 'default') {
            if (isset($connections['default'])) {
                $name = $connections['default'];
            } else {
                throw new AppException('Error, default connection doesn\'t exist');
            }

        } else {
            $name = self::$connectionName;
        }

        if (isset($connections[$name])) {
            self::$settings = $connections[$name];
        } else {
            throw new AppException("Error, {$name} connection does not exist");
        }
    }

    /**
     * Build and returns error mode string
     *
     * @return string
     */
    private static function getErrorMode()
    {
        return App::env('ENV') === 'dev' ? '\PDO::ERRMODE_WARNING' : '\PDO::ERRMODE_EXCEPTION';
    }

    /**
     * Build and return DSN string
     *
     * @return string
     */
    private static function getDSN()
    {
        //get settings
        $conf = self::$settings;
        return "{$conf['db_type']}:host={$conf['db_host']};port={$conf['db_port']};dbname={$conf['db_name']};charset={$conf['charset']}";
    }
}