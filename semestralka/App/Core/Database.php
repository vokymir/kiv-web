<?php

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use App\Config\Config;
use App\Models\Status;

// to access the DB
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

    // prepare a SQL query in handler
    // return self to concat methods
    public function query(string $sql): self
    {
        $this->stmt = $this->dbh->prepare($sql);
        return $this;
    }

    // execute SQL query prepared by calling 'self->query' before
    // return bool if is success
    public function execute(): bool
    {
        return $this->stmt->execute();
    }

    // fetch first element in DB
    public function fetchFirst(): mixed
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    // fetch all from DB
    public function fetchAll(): array
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // bind a variable to query parameter
    // return self to concat methods
    public function bind(string $param, mixed $value, mixed $type = null): self
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
                case $value instanceof Status:
                    $value = $value->value;
                    $type = PDO::PARAM_INT;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    // bind more variables to query parameters
    // return self to concat methods
    // @param array<string, mixed> $params Associative array of parameter names and values
    public function bindBulk(array $params): self
    {
        foreach ($params as $param => $value) {
            $this->bind($param, $value);
        }
        return $this;
    }
}
