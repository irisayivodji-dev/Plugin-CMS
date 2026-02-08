<?php

namespace App\Lib\Database;

class DatabaseConnexion {
    private \PDO | null $connexion;

    public function setConnexion(Dsn $dsn): void {
        $this->connexion = new \PDO($dsn->getDsn(), $dsn->getUser(), $dsn->getPassword());
    }

    public function getConnexion(): \PDO {
        return $this->connexion;
    }

    public function deleteConnexion(): void {
        $this->connexion = null;
    }
}
