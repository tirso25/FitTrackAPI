<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'exercises', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'UNIQ_EXERCISE_NAME', fields: ['name'])
])]
#[UniqueEntity(fields: ['name'], message: 'This exercise name is already taken')]
class Exercises
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id_exe = null;

    #[ORM\Column(length: 30, type: Types::STRING, unique: true)]
    #[Assert\NotBlank(message: "The name cannot be empty")]
    private ?string $name = null;

    #[ORM\Column(length: 500, type: Types::STRING)]
    #[Assert\NotBlank(message: "The description cannot be empty")]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Categories::class, inversedBy: 'exercises')]
    #[ORM\JoinColumn(name: 'category', referencedColumnName: 'id_cat', nullable: false)]
    #[Assert\NotNull]
    private ?Categories $category = null;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\NotNull]
    private ?int $likes = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $active = null;

    #[ORM\OneToMany(targetEntity: FavoriteExercises::class, mappedBy: 'exercise', orphanRemoval: true)]
    private Collection $favoriteExercises;

    public function __construct()
    {
        $this->favoriteExercises = new ArrayCollection();
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

    public function getCategory(): ?Categories
    {
        return $this->category;
    }

    public function setCategory(Categories $category): static
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

    public function getFavoriteExercises()
    {
        return $this->favoriteExercises;
    }

    public function setFavoriteExercises($favoriteExercises)
    {
        $this->favoriteExercises = $favoriteExercises;
        return $this;
    }

    public static function validate($data)
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    public static function exerciseExisting(string $name, $entityManager)
    {
        $exercise = $entityManager->getRepository(Exercises::class)->findOneBy(['name' => $name]);

        return $exercise !== null;
    }

    public static function exerciseExisting2(int $id, string $name, $entityManager)
    {
        $query2 = $entityManager->createQuery(
            'SELECT u.name FROM App\Entity\Exercises u WHERE u.id_exe = :id'
        )->setParameter('id', $id);

        $result = $query2->getOneOrNullResult();

        if (!$result || !isset($result['name'])) {
            return false;
        }

        $nameDB = $result['name'];

        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\Exercises u WHERE u.name = :name AND u.name != :nameDB'
        )->setParameters([
            'name' => $name,
            'nameDB' => $nameDB
        ]);

        return $query->getOneOrNullResult() !== null;
    }

    public static function isActive(int $id, $entityManager)
    {
        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\Exercises u WHERE u.id_exe = :id AND u.active = true'
        )->setParameter('id', $id);

        return $query->getOneOrNullResult() !== null;
    }
}
