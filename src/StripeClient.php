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
  	$data = [
		  "email" => $user->getEmail()
	  ];

  	if($paymentToken){
  		$data['source'] = $paymentToken;
	  }

  	$customer = \Stripe\Customer::create($data);

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
    	try {
        $invoice->pay();
	    } catch (\Stripe\Error\Card $e) {
				$invoice->closed = true;
				$invoice->save();

				throw $e;
	    }
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

		$originalPlanId = $stripeSubscription->plan->id;
		$currentPeriodStart = $stripeSubscription->current_period_start;

		$stripeSubscription->plan = $newPlan->getPlanId();
		$stripeSubscription->save();

		// if the duration did not change, Stripe will not charge them immediately
		// but we *do* want them to be charged immediately
		// if the duration changed, an invoice was already created and paid
		if($stripeSubscription->current_period_start == $currentPeriodStart) {
			try {
				$this->createInvoice($user);
			} catch (\Stripe\Error\Card $e) {
				$stripeSubscription->plan = $originalPlanId;
				$stripeSubscription->prorate = false;
				$stripeSubscription->save();

				throw $e;
			}
		}

		return $stripeSubscription;
	}

	public function findCoupon($code){
		return \Stripe\Coupon::retrieve($code);
	}

	/**
	 * @param User $user
	 * @return /Stripe/Customer|null
	 */
	public function findCustomer(User $user){
		return \Stripe\Customer::retrieve($user->getStripeCustomerId());
	}

	/**
	 * @param User $user
	 * @return \Stripe\Invoice[]
	 */
	public function findPaidInvoices(User $user){
		$allInvoices = \Stripe\Invoice::all([
			"customer" => $user->getStripeCustomerId()
		]);

		$iterator = $allInvoices->autoPagingIterator();

		$invoices = [];
		foreach ($iterator as $invoice){
			if($invoice->paid) {
				$invoices[] = $invoice;
			}
		}

		return $invoices;
	}

	/**
	 * @param $invoiceId
	 * @return \Stripe\Invoice
	 */
	public function findInvoice($invoiceId){
		return \Stripe\Invoice::retrieve($invoiceId);
	}
}