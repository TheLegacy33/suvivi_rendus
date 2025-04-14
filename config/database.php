<?php
class Database {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            $dsn = "mysql:host=localhost;dbname=mvc_app;charset=utf8";
            self::$instance = new PDO($dsn, "root", "");
        }
        return self::$instance;
    }
}
