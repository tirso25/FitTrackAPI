<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;

class Excercises
{
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: Types::INTEGER)]
    private $id_exe = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private $name = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private $description = null;

    #[ORM\Column(length: 255, type: Types::STRING)]
    private $category = null;

    #[ORM\Column(length: 32, type: Types::INTEGER)]
    private $likes = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private $active = null;

    public function getIdExe()
    {
        return $this->id_exe;
    }

    public function setIdExe($id_exe)
    {
        $this->id_exe = $id_exe;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    public function getLikes()
    {
        return $this->likes;
    }

    public function setLikes($likes)
    {
        $this->likes = $likes;

        return $this;
    }

    public static function exerciseExisting($name, EntityManagerInterface $entityManager)
    {
        $exercise = $entityManager->getRepository(Excercises::class)->findOneBy(['name' => $name]);

        return $exercise !== null;
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
}
