<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'UNIQ_USER_EMAIL', fields: ['email']),
    new ORM\UniqueConstraint(name: 'UNIQ_USER_USERNAME', fields: ['username'])
])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already taken')]
class Users
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_usr = null;

    #[ORM\Column(length: 255, type: Types::STRING, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 20, type: Types::STRING, unique: true)]
    #[Assert\NotBlank]
    private ?string $username = null;

    #[ORM\Column(length: 255, type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $password = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $role = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $active = null;

    #[ORM\OneToMany(targetEntity: ExercisesXUser::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $exercisesXUser;

    public function __construct()
    {
        $this->exercisesXUser = new ArrayCollection();
    }

    public function getIdUsr(): ?int
    {
        return $this->id_usr;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getExercisesXUser(): Collection
    {
        return $this->exercisesXUser;
    }

    public function setExercisesXUser(Collection $exercisesXUser): static
    {
        $this->exercisesXUser = $exercisesXUser;

        return $this;
    }

    public static function validate($data)
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function hashPassword(string $password)
    {
        $options = [
            'cost' => 13,
        ];

        return password_hash($password, PASSWORD_BCRYPT, $options);
    }

    public static function passwordVerify(string $userPassword, $hashedPawword)
    {
        return password_verify($userPassword, $hashedPawword);
    }

    // public static function userExisting(string $email, string $username, $entityManager)
    // {
    //     $emailExisting = $entityManager->getRepository(Users::class)->findOneBy(['email' => $email]);
    //     $usernameExisting = $entityManager->getRepository(Users::class)->findOneBy(['username' => $username]);

    //     // Si alguno de los 2 no es null significa que el usuario ya existe
    //     return $emailExisting !== null || $usernameExisting !== null;
    // }

    public static function userExisting(string $email, string $username, $entityManager)
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

    public static function userExisting2(int $id, string $username, $entityManager)
    {
        $query2 = $entityManager->createQuery(
            'SELECT u.username FROM App\Entity\Users u WHERE u.id_usr = :id'
        )->setParameter('id', $id);

        $result = $query2->getOneOrNullResult();

        if (!$result || !isset($result['username'])) {
            return false;
        }

        $usernameDB = $result['username'];

        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\Users u WHERE u.username = :username AND u.username != :usernameDB'
        )->setParameters([
            'username' => $username,
            'usernameDB' => $usernameDB
        ]);

        return $query->getOneOrNullResult() !== null;
    }


    public static function passwordsMatch(string $email, string $password, $entityManager)
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

    public static function getIdUser(string $emailUsername, $entityManager)
    {
        $query = $entityManager->createQuery(
            'SELECT u.id_usr FROM App\Entity\Users u WHERE u.email = :emailUsername OR u.username = :emailUsername'
        )->setParameter('emailUsername', $emailUsername);

        $id_user = $query->getSingleScalarResult();

        if (!$id_user) {
            return null;
        }

        return $id_user;
    }
}
