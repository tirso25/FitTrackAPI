<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column()]
    private ?int $id_usr = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    public function getIdUsr()
    {
        return $this->id_usr;
    }

    public function setIdUsr($id_usr)
    {
        $this->id_usr = $id_usr;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    public static function validate($data)
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function hashPassword($password)
    {
        $options = [
            'cost' => 13,
        ];

        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    public static function passwordVerify($userPassword, $hashedPawword)
    {
        return password_verify($userPassword, $hashedPawword);
    }

    // public static function userExisting($email, $username, $entityManager)
    // {
    //     $emailExisting = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);
    //     $usernameExisting = $entityManager->getRepository(Users::class)->findOneBy(['username' => $username]);

    //     // Si alguno de los 2 no es null significa que el usuario ya existe
    //     return $emailExisting !== null || $usernameExisting !== null;
    // }

    public static function userExisting($email, $username, $entityManager)
    {
        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\Users u WHERE u.email = :email OR u.username = :username'
        )->setParameters([
            'email' => $email,
            'username' => $username
        ]);

        return $query->getOneOrNullResult() !== null;
    }
}
