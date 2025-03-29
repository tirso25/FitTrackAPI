<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: 'exercisesxuser')]
class ExercisesXUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_exu = null;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'exercisesXUser')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id_usr', nullable: false)]
    private ?Users $user = null;

    #[ORM\ManyToOne(targetEntity: Exercises::class, inversedBy: 'exercisesXUser')]
    #[ORM\JoinColumn(name: 'exercise_id', referencedColumnName: 'id_exe', nullable: false)]
    private ?Exercises $exercise = null;

    public function getIdExu(): ?int
    {
        return $this->id_exu;
    }

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
}
