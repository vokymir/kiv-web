<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use App\Config\Config;

class Database
{
    private string $host = Config::DB_HOST;
    private string $user = Config::DB_USER;
    private string $password = Config::DB_PASS;
    private string $dbname = Config::DB_NAME;
    private string $dbport = Config::DB_PORT;

    private PDO $dbh; // database handler
    private PDOStatement|false $stmt; // SQL statement
    private string $error; // error message

    public function __construct()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';port=' . $this->dbport; // data source name

        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->password, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            echo $this->error;
        }
    }

    public function query(string $sql): Database
    {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }

    public function execute(): bool
    {
        return $this->stmt->execute();
    }

    public function fetchFirst(): mixed
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchAll(): array
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function bind(string $param, mixed $value, mixed $type = null): Database
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }
}
