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
      'Farmer Brent Monthly',
      99,
	    SubscriptionPlan::DURATION_MONTHLY
    );

    $this->plans[] = new SubscriptionPlan(
      'New_Zealander_Monthly',
      'New Zealander Monthly',
      199,
	    SubscriptionPlan::DURATION_MONTHLY
    );

	  $this->plans[] = new SubscriptionPlan(
		  'Farmer_Brent_Yearly',
		  'Farmer Brent Yearly',
		  990,
		  SubscriptionPlan::DURATION_YEARLY
	  );

	  $this->plans[] = new SubscriptionPlan(
		  'New_Zealander_Yearly',
		  'New Zealander Yearly',
		  1990,
		  SubscriptionPlan::DURATION_YEARLY
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

	/**
	 * @param $currentPlanId
	 * @return SubscriptionPlan|null
	 */
	public function findPlanToChangeTo($currentPlanId){
		if(strpos($currentPlanId, 'Farmer_Brent_Monthly') !== false){
			$newPlanId = 'New_Zealander_Monthly';
		} else {
			$newPlanId = 'Farmer_Brent_Monthly';
		}
		return $this->findPlan($newPlanId);
	}

	public function findPlanForOtherDuration($currentPlanId){
		if (strpos($currentPlanId, 'Monthly') !== false) {
			$newPlanId = str_replace('Monthly', 'Yearly', $currentPlanId);
		} else {
			$newPlanId = str_replace('Yearly', 'Monthly', $currentPlanId);
		}
		return $this->findPlan($newPlanId);
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
		if(!$stripeCustomer->sources->data){
			// the customer may not have a card on file
			return;
		}
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
