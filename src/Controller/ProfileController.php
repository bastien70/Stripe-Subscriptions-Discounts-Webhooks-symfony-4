<?php

namespace App\Controller;


use App\StripeClient;
use App\Subscription\SubscriptionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @IsGranted("ROLE_USER")
 */
class ProfileController extends AbstractController {
  /**
   * @var StripeClient
   */
  private $stripeClient;
  /**
   * @var EntityManagerInterface
   */
  private $em;
  /**
   * @var SubscriptionHelper
   */
  private $subscriptionHelper;

  public function  __construct(
    StripeClient $stripeClient,
    EntityManagerInterface $em,
    SubscriptionHelper $subscriptionHelper
  ){
    $this->stripeClient = $stripeClient;
    $this->em = $em;
    $this->subscriptionHelper = $subscriptionHelper;
  }

  /**
   * @Route("/profile", name="profile_account")
   */
  public function accountAction() {
    return $this->render('profile/account.html.twig', [
	    'error' => null,
	    'stripe_public_key' => $this->getParameter('stripe_public_key'),
    ]);
  }

  /**
   * @Route("/profile/subscription/cancel", name="account_subscription_cancel", methods={"POST"})
   *
   */
  public function cancelSubscriptionAction(){
    $stripeClient = $this->stripeClient;

    $stripeSubscription = $stripeClient->cancelSubscription($this->getUser());

    $subscription = $this->getUser()->getSubscription();
    if ($stripeSubscription->status == 'canceled'){
      $subscription->cancel();
    } else {
      $subscription->deactivateSubscription();
    }
    $this->em->persist($subscription);
    $this->em->flush();

    $this->addFlash('success', 'Subscription canceled :)');

    return $this->redirectToRoute('profile_account');
  }

  /**
   * @Route("/profile/subscription/reactivate", name="account_subscription_reactivate", methods={"POST"})
   *
   */
  public function reactivateSubscriptionAction(){
    $stripeClient = $this->stripeClient;

    $stripeSubscription = $stripeClient
      ->reactivateSubscription($this->getUser());

    $this->subscriptionHelper
      ->addSubscriptionToUser($stripeSubscription, $this->getUser());

    $this->addFlash('success', 'Welcome Back!');

    return $this->redirectToRoute('profile_account');
  }

	/**
	 * @Route("/profile/card/update", name="account_update_credit_card", methods={"POST"})
	 *
	 */
	public function updateCreditCardAction(){
	}
}
