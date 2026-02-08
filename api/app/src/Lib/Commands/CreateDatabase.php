<?php


namespace App\Lib\Commands;

use App\Lib\Database\DatabaseConnexion;
use App\Lib\Database\Dsn;


class CreateDatabase extends AbstractCommand {
    
    public function execute(): void
    {
        $db = new DatabaseConnexion();
        $dsn = new Dsn();
        $dsn->addHostToDsn()
            ->addPortToDsn();
        $db->setConnexion($dsn);
        $db->getConnexion()->exec("CREATE DATABASE IF NOT EXISTS {$dsn->getDbName()};");
    }

    public function undo(): void
    {
    }

    public function redo(): void
    {
    }
    
}

?>
