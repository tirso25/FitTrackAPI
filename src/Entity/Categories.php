<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: 'categories', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'UNIQ_CATEGORY_NAME', fields: ['name'])
])]
class Categories
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_cat = null;

    #[ORM\Column(length: 50, type: Types::STRING, unique: true)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private ?bool $active = null;

    #[ORM\OneToMany(targetEntity: Exercises::class, mappedBy: 'category')]
    private Collection $exercises;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
    }

    public function getIdCat(): ?int
    {
        return $this->id_cat;
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

    public function getExercises()
    {
        return $this->exercises;
    }

    public function setExercises($exercises)
    {
        $this->exercises = $exercises;

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

    public static function categoryExisting(string $name, $entityManager)
    {
        $role = $entityManager->getRepository(Categories::class)->findOneBy(['name' => $name]);

        return $role !== null;
    }

    public static function categoryExisting2(int $id, string $name, $entityManager)
    {
        $query2 = $entityManager->createQuery(
            'SELECT u.name FROM App\Entity\Categories u WHERE u.id_cat = :id'
        )->setParameter('id', $id);

        $result = $query2->getOneOrNullResult();

        if (!$result || !isset($result['name'])) {
            return false;
        }

        $nameDB = $result['name'];

        $query = $entityManager->createQuery(
            'SELECT u FROM App\Entity\Categories u WHERE u.name = :name AND u.name != :nameDB'
        )->setParameters([
            'name' => $name,
            'nameDB' => $nameDB
        ]);

        return $query->getOneOrNullResult() !== null;
    }
}
