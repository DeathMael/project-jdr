<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\BookingRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Booking {
	/**
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue
	 * @ORM\Id
	 */
	private $id;

	/**
	 * @ORM\Column(type="datetime")
	 * @Assert\NotBlank
	 * @Assert\GreaterThan("today Europe/Paris")
	 */
	private $beginAt;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 * @Assert\Expression(
	 *     "this.getBeginAt() < this.getEndAt()",
	 *     message="La date fin ne doit pas être antérieure à la date début"
	 * )
	 */
	private $endAt = null;

	/**
	 * @ORM\Column(type="string", length=255)
	 * @Assert\NotBlank
	 */
	private $title;

	/**
	 * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="booking")
	 */
	private $users;

	/**
	 * @ORM\Column(type="text", nullable=true)
	 */
	private $description;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $updated_at;

	/**
	 * @ORM\Column(type="datetime")
	 */
	private $created_at;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $googleid;


	public function __construct() {
		$this->users = new ArrayCollection();
	}




	public function getId():  ? int {
		return $this->id;
	}

	public function getBeginAt() :  ? \DateTimeInterface {
		return $this->beginAt;
	}

	public function setBeginAt(\DateTimeInterface $beginAt) : self{
		$this->beginAt = $beginAt;

		return $this;
	}

	public function getEndAt():  ? \DateTimeInterface {
		return $this->endAt;
	}

	public function setEndAt( ? \DateTimeInterface $endAt = null) : self{
		$this->endAt = $endAt;

		return $this;
	}

	public function getTitle() :  ? string {
		return $this->title;
	}

	public function setTitle(string $title) : self{
		$this->title = $title;

		return $this;
	}


	/**
	 * Returns an authorized API client.
	 * @return Google_Client the authorized client object
	 */
	public function getClient() {
		$client = new \Google_Client();
		$client->setApplicationName('Google Calendar API PHP Quickstart');
		$client->setScopes(Google_Service_Calendar::CALENDAR);
		$client->setAuthConfig('credentials.json');
		$client->setAccessType('offline');
		$client->setPrompt('select_account consent');

		// Load previously authorized token from a file, if it exists.
		// The file token.json stores the user's access and refresh tokens, and is
		// created automatically when the authorization flow completes for the first
		// time.
		$tokenPath = 'token.json';
		if (file_exists($tokenPath)) {
			$accessToken = json_decode(file_get_contents($tokenPath), true);
			$client->setAccessToken($accessToken);
		}

		// If there is no previous token or it's expired.
		if ($client->isAccessTokenExpired()) {
			// Refresh the token if possible, else fetch a new one.
			if ($client->getRefreshToken()) {
				$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
			} else {
				// Request authorization from the user.
				$authUrl = $client->createAuthUrl();
				printf("Open the following link in your browser:\n%s\n", $authUrl);
				print 'Enter verification code: ';
				$authCode = trim(fgets(STDIN));

				// Exchange authorization code for an access token.
				$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
				$client->setAccessToken($accessToken);

				// Check to see if there was an error.
				if (array_key_exists('error', $accessToken)) {
					throw new Exception(join(', ', $accessToken));
				}
			}
			// Save the token to a file.
			if (!file_exists(dirname($tokenPath))) {
				mkdir(dirname($tokenPath), 0700, true);
			}
			file_put_contents($tokenPath, json_encode($client->getAccessToken()));
		}
		return $client;
	}

	/**
	 * @return Collection|User[]
	 */
	public function getUsers(): Collection {
		return $this->users;
	}

	public function addUser(User $user): self {
		if (!$this->users->contains($user)) {
			$this->users[] = $user;
			$user->setBooking($this);
		}

		return $this;
	}

	public function removeUser(User $user): self {
		if ($this->users->contains($user)) {
			$this->users->removeElement($user);
			// set the owning side to null (unless already changed)
			if ($user->getBooking() === $this) {
				$user->setBooking(null);
			}
		}

		return $this;
	}

	public function getDescription():  ? string {
		return $this->description;
	}

	public function setDescription( ? string $description) : self{
		$this->description = $description;

		return $this;
	}

	public function getUpdatedAt() :  ? \DateTimeInterface {
		return $this->updated_at;
	}

	/**
	 * @ORM\PreUpdate
	 */
	public function setUpdatedAt() {
		$this->updated_at = new \DateTime('now', new \DateTimeZone("Europe/Paris"));

		return $this;
	}

	public function getCreatedAt() :  ? \DateTimeInterface {
		return $this->created_at;
	}

	/**
	 * @ORM\PrePersist
	 */
	public function setCreatedAt() : self{
		$this->created_at = new \DateTime('now', new \DateTimeZone("Europe/Paris"));

		return $this;
	}

	public function getGoogleid():  ? string {
		return $this->googleid;
	}

	public function setGoogleid(string $googleid) : self{
		$this->googleid = $googleid;

		return $this;
	}

	public function __toString() {
		return $this->getTitle() . ' du ' . date_format($this->getBeginAt(), "Y-m-d\TH:i:s");
	}
}