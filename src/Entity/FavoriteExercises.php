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

    public static function getFavouriteExercisesByUserId(int $id, $entityManager): array
    {
        $query = $entityManager->createQuery(
            'SELECT fe
            FROM App\Entity\FavoriteExercises fe
            JOIN fe.user u
            WHERE fe.user = :id_user AND u.public = true'
        )->setParameter('id_user', $id);

        $favorites = $query->getResult();

        $data = [];

        if (empty($favorites)) {
            $data = ['type' => 'warning', 'message' => 'This user has a private profile or no bookmarks'];
        }

        foreach ($favorites as $favourite) {
            $data[] = [
                'type' => 'success',
                'message' => [
                    'id_exe' => $favourite->getExercise()->getIdExe(),
                    'name_exe' => $favourite->getExercise()->getName(),
                    'description_exe' => $favourite->getExercise()->getDescription(),
                    'category_exe' => $favourite->getExercise()->getCategory()->getName()
                ]
            ];
        }

        return $data;
    }
}
