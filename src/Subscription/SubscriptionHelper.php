<?php

namespace App\Subscription;

class SubscriptionHelper{
  /** @var SubscriptionPlan[] */
  private $plans = [];

  public function __construct() {
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
}
