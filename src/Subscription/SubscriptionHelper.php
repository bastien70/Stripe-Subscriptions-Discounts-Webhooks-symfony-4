<?php

namespace App\Subscription;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionHelper{
  /** @var SubscriptionPlan[] */
  private $plans = [];
  /**
   * @var EntityManagerInterface
   */
  private $em;

  public function __construct(EntityManagerInterface $em) {
    $this->plans[] = new SubscriptionPlan(
      'Farmer_Brent_Monthly',
      'Farmer Brent',
      99
    );

    $this->plans[] = new SubscriptionPlan(
      'New_Zealander_Monthly',
      'New Zealander',
      199
    );
    $this->em = $em;
  }

  /**
   * @param $planId
   * @return SubscriptionPlan|null
   */
  public function findPlan($planId){
    foreach ($this->plans as $plan) {
      if ($plan->getPlanId() == $planId) {
        return $plan;
      }
    }
  }

  public function addSubscriptionToUser(\Stripe\Subscription $stripeSubscription, User $user){
    $subscription = $user->getSubscription();
    if(!$subscription){
      $subscription = new Subscription();
      $subscription->setUser($user);
    }

    $subscription->activateSubscription(
      $stripeSubscription->plan->id,
      $stripeSubscription->id
    );

    $this->em->persist($subscription);
    $this->em->flush($subscription);
  }
}
