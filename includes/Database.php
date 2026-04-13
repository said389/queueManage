<?php

class Database {

    public static $tables = array(
        'ban',
        'desk',
        'device',
        'display_main',
        'office',
        'operator',
        'ticket_exec',
        'ticket_in',
        'ticket_stats',
        'topical_domain',
    );

    private static $dbConnection = null;
    private static $lockTables = false; // ✅ Disable LOCK TABLES

    private function __construct() {}

    public static function getConnection() {
        if ( self::$dbConnection == null ) {

            global $gvDbConfig;

            $host = $gvDbConfig['host'];
            $database = $gvDbConfig['database'];
            $username = $gvDbConfig['username'];
            $password = $gvDbConfig['password'];
            $port = isset($gvDbConfig['port']) ? $gvDbConfig['port'] : '3306';

            $dsn = "mysql:host=$host;port=$port;dbname=$database";

            self::$dbConnection = new PDO(
                $dsn,
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
                )
            );

            // ✅ Lock tables disabled
        }
        return self::$dbConnection;
    }

    public static function prepareStatement( $sql ) {
        $conn = self::getConnection();
        return $conn->prepare( $sql );
    }

    public static function hasBeenUsed() {
        return self::$dbConnection !== null;
    }

    public static function commit() {
        if (!self::$dbConnection) {
            throw new Exception(__METHOD__ . " Unable to commit, unused database.");
        }
        // ✅ No LOCK tables -> no COMMIT needed
    }

    public static function lockTables( $value ) {
        // ✅ Completely disabled (MariaDB/XAMPP compatibility)
        self::$lockTables = false;
    }
}