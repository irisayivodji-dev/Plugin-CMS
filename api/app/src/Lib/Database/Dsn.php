<?php

namespace App\Lib\Database;

class Dsn {
    const string DATABASE_CONFIG_PATH = __DIR__ . '/../../../config/database.json';
    
    private string $host;
    private string $user;
    private string $password;
    private string $dbname;
    private int $port;
    private string $dsn;

    public function __construct() {
        $config = self::getConfig();

        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->dbname = $config['database'];
        $this->port = $config['port'];
        $this->dsn = 'pgsql:';
    }

    public function getUser(): string {
        return $this->user;
    }

    public function getPassword(): string {
        return $this->password;
    }

    public function addHostToDsn(): self {
        $this->dsn .= "host=$this->host;";
        return $this;
    }

    public function addDbnameToDsn(): self {
        $this->dsn .= "dbname=$this->dbname;";
        return $this;
    }

    public function addPortToDsn(): self {
        $this->dsn .= "port=$this->port;";
        return $this;
    }

    public function getDsn(): string {
        return $this->dsn;
    }

    public function getDbName(): string {
        return $this->dbname;
    }

    private static function getConfig(): array {
        $file = file_get_contents(self::DATABASE_CONFIG_PATH);
        return json_decode($file, true);
    }
    
}
