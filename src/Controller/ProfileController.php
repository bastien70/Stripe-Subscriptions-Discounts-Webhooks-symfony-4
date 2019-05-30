<?php

namespace App\Controller;


use App\StripeClient;
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

  public function  __construct(StripeClient $stripeClient){
    $this->stripeClient = $stripeClient;
  }

  /**
   * @Route("/profile", name="profile_account")
   */
  public function accountAction() {
    return $this->render('profile/account.html.twig');
  }

  /**
   * @Route("/profile/subscription/cancel", name="account_subscription_cancel", methods={"POST"})
   *
   */
  public function cancelSubscriptionAction(){
    $stripeClient = $this->stripeClient;

    $stripeClient->cancelSubscription($this->getUser());

    $this->addFlash('success', 'Subscription canceled :)');

    $this->redirectToRoute('profile_account');
  }
}
