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

    $periodEnd = \DateTime::createFromFormat(
      'U',
      $stripeSubscription->current_period_end
    );

    $subscription->activateSubscription(
      $stripeSubscription->plan->id,
      $stripeSubscription->id,
      $periodEnd
    );

    $this->em->persist($subscription);
    $this->em->flush($subscription);
  }

  public function updateCardDetails(User $user, \Stripe\Customer $stripeCustomer){
    $cardDetails = $stripeCustomer->sources->data[0];
    $user->setCardBrand($cardDetails->brand);
    $user->setCardLast4($cardDetails->last4);

    $this->em->persist($user);
    $this->em->flush($user);
  }

	public function fullyCancelSubscription(Subscription $subscription){
		$subscription->cancel();
		$this->em->persist($subscription);
		$this->em->flush($subscription);
	}

	public function handleSubscriptionPaid(
		Subscription $subscription,
		\Stripe\Subscription $stripeSubscription
	){
		$newPeriodEnd = \DateTime::createFromFormat(
			'U',
			$stripeSubscription->current_period_end
		);

		//send email if is renewal
		$isRenewal = $newPeriodEnd > $subscription->getBillingPeriodEndsAt();
		
		$subscription->setBillingPeriodEndsAt($newPeriodEnd);
		$this->em->persist($subscription);
		$this->em->flush($subscription);
	}

}
