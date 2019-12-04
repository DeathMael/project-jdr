<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser implements UserInterface
{
    public function __construct()
        {
            parent:: __construct();
        }
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $firstname;

    /**
     * @ORM\Column(type="integer")
     */
    private $rank;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event", inversedBy="users")
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="users")
     */
    private $project;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername($username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail($email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword($password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(int $rank): self
    {
        $this->rank = $rank;

        return $this;
    }

    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        // TODO: Implement getSalt() method.
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getName()
    {
        return $this->getFirstName().' '.$this->getLastName();
    }

    public function getFormatedRank()
    {
        switch ($this->getRank())
        {
            case 0 : return 'Orbis Tertius';
                break;
            case 1 : return 'Orbis Secondus';
                break;
            case 2 : return 'Orbis Primus';
                break;
            default : return 'Non renseigné';
        }
    }

    public function getFormatedRoles()
    {
        switch ($this->getRoles())
        {
            case ["[ROLE_USER]"]: return 'Utilisateur';
            break;
            case ["ROLE_ADMIN"]: return 'Administrateur';
            break;
            case ["[ROLE_USER]","ROLE_ADMIN"]: return 'Admin/User';
            break;
            default: return null;
        }
    }

    public function getFormatedEvent()
    {
        if($this->getEvent()!=null)
        {
            return $this->getEvent()->getTitle().' : '.date_format ( $this->getEvent()->getDate() , 'd/m/Y');
        }
        else return null;
    }

    public function getFormatedProject()
    {
        if($this->getProject()!=null)
        {
            return 'Projet n°'.$this->getProject()->getId().' : '.$this->getProject()->getStatuteType();
        }
        else return null;
    }
}
