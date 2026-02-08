<?php

namespace App\Repositories;

use App\Lib\Repositories\AbstractRepository;
use App\Entities\User;

class UserRepository extends AbstractRepository {
    
    public function getTable(): string {
        return 'users'; // Override pour correspondre au nom de la table
    }
    
    public function getOneResult() {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, User::class);
        return $this->query->fetch();
    }

    public function getAllResults(): array {
        $this->query->setFetchMode(\PDO::FETCH_CLASS, User::class);
        return $this->query->fetchAll();
    }
    
    public function findByEmail(string $email): ?User 
    {
        return $this->findOneBy(['email' => $email]);
    }
}
?>