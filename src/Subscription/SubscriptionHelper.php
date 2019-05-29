<?php

namespace App\Subscription;

class SubscriptionHelper{
  /** @var SubscriptionPlan[] */
  private $plans = [];

  public function __construct() {
        // todo - add the plans
//        $this->plans[] = new SubscriptionPlan(
//            'STRIPE_PLAN_KEY',
//            'OUR PLAN NAME',
//            'PRICE'
//        );
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
