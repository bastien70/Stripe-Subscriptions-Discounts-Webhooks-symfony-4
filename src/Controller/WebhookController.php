<?php
namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController {

  /**
   * @Route("/webhooks/stripe", name="webhook_stripe")
   */
  public function stripeWebhookAction(Request $request){
    $data = json_decode($request->getContent(), true);

    if($data === null){
      throw new \Exception('Bad JSON body from Stripe!');
    }

    $eventId = $data['id'];
    
    return new Response('baaa!');
  }
}