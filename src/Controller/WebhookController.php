<?php
namespace App\Controller;


use App\Entity\StripeEventLog;
use App\StripeClient;
use App\Subscription\SubscriptionHelper;
use Doctrine\ORM\EntityManagerInterface;
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
	/**
	 * @var EntityManagerInterface
	 */
	private $em;

	public function __construct(
		StripeClient $stripeClient,
		SubscriptionHelper $subscriptionHelper,
		EntityManagerInterface $em
	){
		$this->stripeClient = $stripeClient;
		$this->subscriptionHelper = $subscriptionHelper;
		$this->em = $em;
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
	  $existingLog = $this->em->getRepository('App:StripeEventLog')
		  ->findOneBy(['stripeEventId' => $eventId]);
	  if ($existingLog){
		  return new Response('Event previously handled!');
	  }

	  $log = new StripeEventLog($eventId);
	  $this->em->persist($log);
	  $this->em->flush($log);
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
					  $user = $subscription->getUser();

					  $stripeCustomer = $this->stripeClient->findCustomer($user);

					  $hasCardOnFile = count($stripeCustomer->sources->data) > 0;

					  // todo - send the user an email about the problem
					  // use hasCardOnFile to customize this
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