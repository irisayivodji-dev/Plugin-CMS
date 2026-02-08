<?php

namespace App\Entities;

use App\Lib\Annotations\ORM\AutoIncrement;
use App\Lib\Annotations\ORM\Column;
use App\Lib\Annotations\ORM\Id;
use App\Lib\Annotations\ORM\ORM;
use App\Lib\Entities\AbstractEntity;

#[ORM]
class User extends AbstractEntity {

    #[Id]
    #[AutoIncrement]
    #[Column(type: 'int')]
    public int $id;
    
    #[Column(type: 'varchar', size: 255)]
    public string $email;

    #[Column(type: 'varchar', size: 255)]
    public string $password;
    
    #[Column(type: 'varchar', size: 255)]
    public string $firstname;
    
    #[Column(type: 'varchar', size: 255)]
    public string $lastname;
    
    #[Column(type: 'varchar', size: 20)]
    public string $role;

    #[Column(type: 'timestamp')]
    public string $created_at;

    #[Column(type: 'timestamp')]
    public string $updated_at;

    public function getId(): int
    {
        return $this->id;
    }

    public function hashPassword(string $plainPassword): void 
    {
        $this->password = password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    public function verifyPassword(string $plainPassword): bool 
    {
        return password_verify($plainPassword, $this->password);
    }
}
?>