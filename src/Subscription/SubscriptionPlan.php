<?php

namespace App\Subscription;

class SubscriptionPlan {
	const DURATION_MONTHLY = 'monthly';
	const DURATION_YEARLY = 'yearly';
  private $planId;

  private $name;

  private $price;

	private $duration;

  public function __construct($planId, $name, $price, $duration=self::DURATION_MONTHLY) {
    $this->planId = $planId;
    $this->name = $name;
    $this->price = $price;
    $this->duration=$duration;
  }

  public function getPlanId() {
    return $this->planId;
  }

  public function getName() {
    return $this->name;
  }

  public function getPrice() {
    return $this->price;
  }

	/**
	 * @return string
	 */
	public function getDuration(): string{
		return $this->duration;
	}
}