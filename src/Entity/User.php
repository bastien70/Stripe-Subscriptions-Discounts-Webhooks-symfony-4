<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface {
  /**
   * @ORM\Id()
   * @ORM\GeneratedValue()
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", unique=true, nullable=true)
   */
  private $stripeCustomerId;

  /**
   * @ORM\Column(type="string", length=180, unique=true)
   */
  private $email;

  /**
   * @ORM\Column(type="json")
   */
  private $roles = [];

  /**
   * @var string The hashed password
   * @ORM\Column(type="string")
   */
  private $password;

  /**
   * @ORM\OneToOne(targetEntity="Subscription", mappedBy="user")
   */
  private $subscription;

  /**
   * @ORM\Column(type="string", nullable=true)
   */
  private $cardBrand;

  /**
   * @ORM\Column(type="string", length=4, nullable=true)
   */
  private $cardLast4;

  public function getId(): ?int {
    return $this->id;
  }

  public function getEmail(): ?string {
    return $this->email;
  }

  /**
   * @return Subscription
   */
  public function getSubscription() {
    return $this->subscription;
  }

  public function setEmail(string $email): self {
    $this->email = $email;

    return $this;
  }

  /**
   * A visual identifier that represents this user.
   *
   * @see UserInterface
   */
  public function getUsername(): string {
    return (string) $this->email;
  }

  /**
   * @see UserInterface
   */
  public function getRoles(): array {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  public function setRoles(array $roles): self {
    $this->roles = $roles;

    return $this;
  }

  /**
   * @see UserInterface
   */
  public function getPassword(): string {
    return (string) $this->password;
  }

  public function setPassword(string $password): self{
    $this->password = $password;

    return $this;
  }

  /**
   * @see UserInterface
   */
  public function getSalt(){
    // not needed when using the "bcrypt" algorithm in security.yaml
  }

  /**
   * @see UserInterface
   */
  public function eraseCredentials(){
    // If you store any temporary, sensitive data on the user, clear it here
    // $this->plainPassword = null;
  }

  /**
   * @return mixed
   */
  public function getStripeCustomerId(){
    return $this->stripeCustomerId;
  }

  /**
   * @param mixed $stripeCustomerId
   */
  public function setStripeCustomerId($stripeCustomerId): void{
    $this->stripeCustomerId = $stripeCustomerId;
  }

  /**
   * @return mixed
   */
  public function getCardLast4(){
    return $this->cardLast4;
  }

  /**
   * @param mixed $cardLast4
   */
  public function setCardLast4($cardLast4): void {
    $this->cardLast4 = $cardLast4;
  }

  /**
   * @return mixed
   */
  public function getCardBrand(){
    return $this->cardBrand;
  }

  /**
   * @param mixed $cardBrand
   */
  public function setCardBrand($cardBrand): void{
    $this->cardBrand = $cardBrand;
  }

  public function hasActiveSubscription(){
    return $this->getSubscription() && $this->getSubscription()->isActive();
  }
}
