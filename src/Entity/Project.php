<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectRepository")
 */
class Project
{
    const STATUTE = [
        1 => "Maitre de Jeu",
        0 => "Joueur"
    ];
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $statute;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="project")
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatute(): ?int
    {
        return $this->statute;
    }

    public function setStatute(int $statute): self
    {
        $this->statute = $statute;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setProject($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getProject() === $this) {
                $user->setProject(null);
            }
        }

        return $this;
    }
     public function getStatuteType(){
        return self::STATUTE[$this->statute];
     }

    public function __toString()
    {
        return 'Projet n°'.$this->getId().' : '.$this->getStatuteType();
    }
}
