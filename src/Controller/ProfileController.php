<?php

namespace App\Controller;


use App\StripeClient;
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

  public function  __construct(StripeClient $stripeClient, EntityManagerInterface $em){
    $this->stripeClient = $stripeClient;
    $this->em = $em;
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

    $subscription = $this->getUser()->getSubscription();
    $subscription->deactivateSubscription();
    $this->em->persist($subscription);
    $this->em->flush();

    $this->addFlash('success', 'Subscription canceled :)');

    return $this->redirectToRoute('profile_account');
  }
}
