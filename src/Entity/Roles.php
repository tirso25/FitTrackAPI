<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'roles', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'UNIQ_ROLE_NAME', fields: ['name'])
])]
class Roles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_role = null;

    #[ORM\Column(length: 50, type: Types::STRING, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 50)]
    private ?string $name = null;

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
}
