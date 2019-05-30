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

    $subscription->cancel_at_period_end = true;
    $subscription->save();
  }
}