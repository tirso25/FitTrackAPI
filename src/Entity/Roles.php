<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'roles', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'UNIQ_ROLE_NAME', fields: ['name'])
])]
#[UniqueEntity(fields: ['name'], message: 'This role name is already taken')]
class Roles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_role = null;

    #[ORM\Column(length: 50, type: Types::STRING, unique: true)]
    #[Assert\NotBlank(message: "The name cannot be empty")]
    private ?string $name = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $active = null;

    #[ORM\OneToMany(targetEntity: Users::class, mappedBy: 'role')]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getIdRole(): ?int
    {
        return $this->id_role;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getUsers(): Collection
    {
        return $this->users;
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

    public static function validate($data)
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function roleExisting(string $name, $entityManager)
    {
        $role = $entityManager->getRepository(Roles::class)->findOneBy(['name' => $name]);

        return $role !== null;
    }

    public static function roleExisting2(int $id, string $name, $entityManager)
    {
        $query2 = $entityManager->createQuery(
            'SELECT u.name FROM App\Entity\Roles u WHERE u.id_role = :id'
        )->setParameter('id', $id);

        $result = $query2->getOneOrNullResult();

        if (!$result || !isset($result['name'])) {
            return false;
        }

        $nameDB = $result['name'];

        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\Roles u WHERE u.name = :name AND u.name != :nameDB'
        )->setParameters([
            'name' => $name,
            'nameDB' => $nameDB
        ]);

        return $query->getOneOrNullResult() !== null;
    }
}
