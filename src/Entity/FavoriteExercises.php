<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: 'favorite_exercises')]
class FavoriteExercises
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_fe = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'favorite_exercises')]
    #[ORM\JoinColumn(name: 'id_usr', referencedColumnName: 'id_usr', nullable: false)]
    private ?Users $user = null;

    #[ORM\ManyToOne(targetEntity: Exercises::class, inversedBy: 'favorite_exercises')]
    #[ORM\JoinColumn(name: 'id_exe', referencedColumnName: 'id_exe', nullable: false)]
    private ?Exercises $exercise = null;

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getExercise(): ?Exercises
    {
        return $this->exercise;
    }

    public function setExercise(?Exercises $exercise): self
    {
        $this->exercise = $exercise;
        return $this;
    }

    public function getIdFe()
    {
        return $this->id_fe;
    }
}
