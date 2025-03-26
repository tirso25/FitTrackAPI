<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_usr = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $email = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $username = null;

    #[ORM\Column(length: 255, type: Types::TEXT)]
    private ?string $password = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $role = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $active = null;

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

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;

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
        //Me retorna un boolean true en el caso de que si se encuentre un usuario y un false cuando es null(no existe el usuario)
        return $query->getOneOrNullResult() !== null;
    }

    public static function passwordsMatch($email, $password, $entityManager)
    {
        if (Users::userExisting($email, $email, $entityManager)) {
            $query = $entityManager->createQuery(
                'SELECT u.password FROM App\Entity\Users u WHERE u.email = :email OR u.username = :username'
            )->setParameters([
                'email' => $email,
                'username' => $email
            ]);

            $hashedPassword = $query->getSingleScalarResult();

            return Users::passwordVerify($password, $hashedPassword);
        }

        return false;
    }
}
