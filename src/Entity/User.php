<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="fos_user")
 * @UniqueEntity(fields="email", message="L'adresse email renseigné est déjà prise par un autre utilisateur !")
 * @UniqueEntity(fields="username", message="Le pseudo renseigné est déjà prise par un autre utilisateur !")
 */
class User extends BaseUser implements UserInterface {
	public function __construct() {
		parent::__construct();
	}
	/**
	 * @ORM\Id()
	 * @ORM\GeneratedValue()
	 * @ORM\Column(type="integer")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message = "Le nom doit être défini !")
     * @Assert\Type(type="string", message = "Le nom doit être une chaîne de caractère !")
     * @Assert\Length(min = 3, max = 50,
     *     minMessage = "Le nom doit contenir au moins {{ limit }} caractères !",
     *     maxMessage = "Le nom doit contenir moins de {{ limit }} caractères !"
     * )
	 */
	private $lastname;

	/**
	 * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message = "Le prénom doit être défini !")
     * @Assert\Type(type="string", message = "Le prénom doit être une chaîne de caractère !")
     * @Assert\Length(min = 3, max = 50,
     *     minMessage = "Le prénom doit contenir au moins {{ limit }} caractères !",
     *     maxMessage = "Le prénom doit contenir moins de {{ limit }} caractères !"
     * )
	 */
	private $firstname;

	/**
	 * @ORM\Column(type="integer")
     * @Assert\NotNull(message = "Le rang doit être défini !")
     * @Assert\Type(type="integer", message = "Le rang doit être un entier !")
     * @Assert\PositiveOrZero(message="Le rang doit être un entier supérieur ou égal à {{ compared_value }} !")
	 */
	private $rank;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Project", inversedBy="users")
	 */
	private $project;

	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\Booking", inversedBy="users")
	 */
	private $booking;

	public function getId():  ? int {
		return $this->id;
	}

	public function getLastname() :  ? string {
		return $this->lastname;
	}

	public function setLastname(string $lastname) : self{
		$this->lastname = $lastname;

		return $this;
	}

	public function getFirstname():  ? string {
		return $this->firstname;
	}

	public function setFirstname(string $firstname) : self{
		$this->firstname = $firstname;

		return $this;
	}

	public function getUsername():  ? string {
		return $this->username;
	}

	public function setUsername($username) : self{
		$this->username = $username;

		return $this;
	}

	public function getEmail():  ? string {
		return $this->email;
	}

	public function setEmail($email) : self{
		$this->email = $email;

		return $this;
	}

	public function getPassword():  ? string {
		return $this->password;
	}

	public function setPassword($password) : self{
		$this->password = $password;

		return $this;
	}

	public function getRank():  ? int {
		return $this->rank;
	}

	public function setRank(int $rank) : self{
		$this->rank = $rank;

		return $this;
	}

	public function getRoles():  ? array
	{
		return $this->roles;
	}

	public function setRoles(array $roles) : self{
		$this->roles = $roles;
		return $this;
	}

	public function getProject():  ? Project {
		return $this->project;
	}

	public function setProject( ? Project $project) : self{
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
	public function getSalt() {
		// TODO: Implement getSalt() method.
	}

	/**
	 * Removes sensitive data from the user.
	 *
	 * This is important if, at any given point, sensitive information like
	 * the plain-text password is stored on this object.
	 */
	public function eraseCredentials() {
		// TODO: Implement eraseCredentials() method.
	}

	public function getName() {
		return $this->getFirstName() . ' ' . $this->getLastName();
	}

	public function getFormatedRank() {
		switch ($this->getRank()) {
		case 0 : return 'Orbis Tertius';
			break;
		case 1:return 'Orbis Secondus';
			break;
		case 2:return 'Orbis Primus';
			break;
		default:return 'Non renseigné';
		}
	}

	public function getFormatedRoles() {
		switch ($this->getRoles()) {
		case ["ROLE_USER"]:return 'Utilisateur';
			break;
		case ["[ROLE_USER]"]:return 'Utilisateur';
			break;
		case ["ROLE_ADMIN"]:return 'Administrateur';
			break;
		case ["[ROLE_USER]", "ROLE_ADMIN"]:return 'Admin/User';
			break;
		default:return null;
		}
	}

	public function getFormatedProject() {
		if ($this->getProject() != null) {
			return 'Projet n°' . $this->getProject()->getId() . ' : ' . $this->getProject()->getStatuteType();
		} else {
			return null;
		}

	}

	public function getBooking():  ? Booking {
		return $this->booking;
	}

	public function setBooking( ? Booking $booking) : self{
		$this->booking = $booking;

		return $this;
	}
}
