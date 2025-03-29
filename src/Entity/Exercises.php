<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
#[ORM\Table(name: 'exercises')]
class Exercises
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_exe = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $name = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $description = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private ?string $category = null;

    #[ORM\Column(length: 32, type: Types::INTEGER)]
    private ?int $likes = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $active = null;

    #[ORM\OneToMany(targetEntity: ExercisesXUser::class, mappedBy: 'exercise', orphanRemoval: true)]
    private Collection $exercisesXUser;

    public function __construct()
    {
        $this->exercisesXUser = new ArrayCollection();
    }

    public function getIdExe(): ?int
    {
        return $this->id_exe;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
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

    public static function exerciseExisting($name, $entityManager)
    {
        $exercise = $entityManager->getRepository(Exercises::class)->findOneBy(['name' => $name]);

        return $exercise !== null;
    }
}
