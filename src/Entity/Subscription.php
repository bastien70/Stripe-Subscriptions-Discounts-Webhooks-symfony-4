<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="subscription")
 */
class Subscription
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue(strategy="AUTO")
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\OneToOne(targetEntity="User", inversedBy="subscription")
   * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
   */
  private $user;

  /**
   * @ORM\Column(type="string")
   */
  private $stripeSubscriptionId;

  /**
   * @ORM\Column(type="string")
   */
  private $stripePlanId;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $endsAt;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   */
  private $billingPeriodEndsAt;

  public function getId()
  {
    return $this->id;
  }

  /**
   * @return User
   */
  public function getUser()
  {
    return $this->user;
  }

  public function setUser(User $user)
  {
    $this->user = $user;
  }

  public function getStripeSubscriptionId()
  {
    return $this->stripeSubscriptionId;
  }

  public function getStripePlanId()
  {
    return $this->stripePlanId;
  }

  /**
   * @return \DateTime
   */
  public function getEndsAt()
  {
    return $this->endsAt;
  }

  public function setEndsAt(\DateTime $endsAt = null)
  {
    $this->endsAt = $endsAt;
  }

  /**
   * @return \DateTime
   */
  public function getBillingPeriodEndsAt()
  {
    return $this->billingPeriodEndsAt;
  }

  public function activateSubscription(
    $stripePlanId,
    $stripeSubscriptionId,
    \DateTime $periodEnd
  )
  {
    $this->stripePlanId = $stripePlanId;
    $this->stripeSubscriptionId = $stripeSubscriptionId;
    $this->billingPeriodEndsAt = $periodEnd;
    $this->endsAt = null;
  }

  public function deactivateSubscription()
  {
    // paid through the end of the period
    $this->endsAt = $this->billingPeriodEndsAt;
    $this->billingPeriodEndsAt = null;
  }

  public function isActive(){
    return $this->endsAt === null || $this->endsAt > new \DateTime();
  }

  public function isCanceled(){
    return $this->endsAt !== null;
  }
}

