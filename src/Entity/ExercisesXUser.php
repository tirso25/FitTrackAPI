<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'exercisesxuser')]
class ExercisesXUser
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'exercisesXUser')]
    #[ORM\JoinColumn(name: 'id_usr', referencedColumnName: 'id_usr', nullable: false)]
    private ?Users $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Exercises::class, inversedBy: 'exercisesXUser')]
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
}
