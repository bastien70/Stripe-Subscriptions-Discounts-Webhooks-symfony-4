<?php
namespace App;


use App\Entity\User;
use App\Subscription\SubscriptionPlan;
use Doctrine\ORM\EntityManagerInterface;


class StripeClient {
  /**
   * @var EntityManagerInterface
   */
  private $em;

  public function __construct($secretKey, EntityManagerInterface $em){
    $this->em = $em;
    \Stripe\Stripe::setApiKey($secretKey);
  }

  public function createCustomer(User $user, $paymentToken){
    $customer = \Stripe\Customer::create([
      "email" => $user->getEmail(),
      "source" => $paymentToken // obtained with Stripe.js
    ]);

    $user->setStripeCustomerId($customer->id);

    $em = $this->em;
    $em->persist($user);
    $em->flush();

    return $customer;
  }

  public function updateCustomerCard(User $user, $paymentToken){
    $customer = \Stripe\Customer::retrieve($user->getStripeCustomerId());
    $customer->source = $paymentToken;
    $customer->save();

    return $customer;
  }

  public function createInvoiceItem($amount, User $user, $description){
    return \Stripe\InvoiceItem::create(array(
      "amount" => $amount,
      "currency" => "usd",
      "customer" => $user->getStripeCustomerId(),
      "description" => $description
    ));
  }

  public function createInvoice(User $user, $payImediately=true){
    $invoice = \Stripe\Invoice::create([
      'customer' => $user->getStripeCustomerId()
    ]);
    if($payImediately) {
      $invoice->pay();
    }
    return $invoice;
  }



  public function createSubscription(User $user, SubscriptionPlan $plan){
    $subscription = \Stripe\Subscription::create([
      "customer" => $user->getStripeCustomerId(),
      "plan" => $plan->getPlanId()
    ]);

    return $subscription;
  }

  public function cancelSubscription(User $user){
    $subscription = \Stripe\Subscription::retrieve(
      $user->getSubscription()->getStripeSubscriptionId()
    );

    $cancelAtPeriodEnd = true;
    $currentPeriodEnd = new \DateTime('@'.$subscription->current_period_end);

    if($subscription->status == 'past_due'){
      $cancelAtPeriodEnd = false;
    } elseif ($currentPeriodEnd < new \DateTime('+1 hour')){
      $cancelAtPeriodEnd = false;
    }

    $subscription->cancel_at_period_end = $cancelAtPeriodEnd;
    $subscription->save();

    return $subscription;
  }

  public function reactivateSubscription(User $user){
    if(!$user->hasActiveSubscription()){
      throw new \LogicException('Subscriptions can only be reactivated if the subscription has not actually ended');
    }

    $subscription = \Stripe\Subscription::retrieve(
      $user->getSubscription()->getStripeSubscriptionId()
    );

    //triggers the refresh of the subscription
    $subscription->plan = $user->getSubscription()->getStripePlanId();
    $subscription->save();

    return $subscription;
  }

	/**
	 * @param $eventId
	 * @return \Stripe\Event
	 */
	public function findEvent($eventId){
		return \Stripe\Event::retrieve($eventId);
	}

	/**
	 * @param $stripeSubscriptionId
	 * @return /Stripe/Subscription
	 */
	public function findSubscription($stripeSubscriptionId){
		return \Stripe\Subscription::retrieve($stripeSubscriptionId);
	}

	public function getUpcomingInvoiceForChangedSubscription(
		User $user,
		SubscriptionPlan $newPlan
	){
		return \Stripe\Invoice::upcoming([
			'customer' => $user->getStripeCustomerId(),
			'subscription' => $user->getSubscription()->getStripeSubscriptionId(),
			'subscription_plan' => $newPlan->getPlanId()
		]);
	}

	public function changePlan(
		User $user,
		SubscriptionPlan $newPlan
	){
		$stripeSubscription = $this->findSubscription(
			$user->getSubscription()->getStripeSubscriptionId()
		);

		$stripeSubscription->plan = $newPlan->getPlanId();
		$stripeSubscription->save();

		$this->createInvoice($user);

		return $stripeSubscription;
	}
}