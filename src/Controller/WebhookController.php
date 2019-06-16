<?php
namespace App\Controller;


use App\StripeClient;
use App\Subscription\SubscriptionHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController {

	/**
	 * @var StripeClient
	 */
	private $stripeClient;
	/**
	 * @var SubscriptionHelper
	 */
	private $subscriptionHelper;

	public function __construct(
		StripeClient $stripeClient,
		SubscriptionHelper $subscriptionHelper
	){
		$this->stripeClient = $stripeClient;
		$this->subscriptionHelper = $subscriptionHelper;
	}

	/**
   * @Route("/webhooks/stripe", name="webhook_stripe")
   */
  public function stripeWebhookAction(Request $request){
    $data = json_decode($request->getContent(), true);

    if($data === null){
      throw new \Exception('Bad JSON body from Stripe!');
    }

    $eventId = $data['id'];
	  $stripeEvent = $this->stripeClient->findEvent($eventId);

	  switch ($stripeEvent->type){
		  case 'customer.subscription.deleted':
			  $stripeSubscriptionId = $stripeEvent->data->object->id;
			  $subscription = $this->findSubscription($stripeSubscriptionId);

			  $this->subscriptionHelper->fullyCancelSubscription($subscription);
			  break;
		  case 'invoice.payment_succeeded':
			  $stripeSubscriptionId = $stripeEvent->data->object->subscription;

			  if($stripeSubscriptionId){
				  $subscription = $this->findSubscription($stripeSubscriptionId);
				  $stripeSubscription = $this->stripeClient->findSubscription($stripeSubscriptionId);

				  $this
					  ->subscriptionHelper
					  ->handleSubscriptionPaid($subscription, $stripeSubscription);
			  }

			  break;
		  case 'invoice.payment_failed':
			  $stripeSubscriptionId = $stripeEvent->data->object->subscription;

			  if ($stripeSubscriptionId){
				  $subscription = $this->findSubscription($stripeSubscriptionId);
				  if($stripeEvent->data->object->attempt_count === 1){
					  //todo send an email
					  $user = $subscription->getUser();
				  }
			  }
			  
			  break;
		  default:
			  // allow this - we'll have Stripe send us everything
			  // throw new \Exception('Unexpected webhook type form Stripe! '.$stripeEvent->type);
	  }

	  return new Response('Event handled '. $stripeEvent->type);
  }

	private function findSubscription($stripeSubscriptionId){
		$subscription = $this
			->getDoctrine()
			->getManager()
			->getRepository('App:Subscription')
			->findOneBy([
				'stripeSubscriptionId' => $stripeSubscriptionId
			]);
		if (!$subscription){
			throw new \Exception('Somehow we have no subscription id '. $stripeSubscriptionId);
		}
		return $subscription;
	}
}