<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'exercise_likes')]
class ExerciseLikes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $exrlike_id = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    private ?int $likes = null;

    #[ORM\ManyToOne(targetEntity: Exercises::class, inversedBy: 'exercise_likes')]
    #[ORM\JoinColumn(name: 'exercise_id', referencedColumnName: 'exercise_id', nullable: false)]
    private ?Exercises $exercise = null;

    public function getExrlikeId(): ?int
    {
        return $this->exrlike_id;
    }

    public function getLikes(): ?int
    {
        return $this->likes;
    }

    public function setLikes(int $likes): static
    {
        $this->likes = $likes;
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
