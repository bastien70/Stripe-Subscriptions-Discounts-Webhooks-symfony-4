<?php


namespace App\Controller;


use App\Entity\Product;
use App\Entity\User;
use App\Store\ShoppingCart;
use App\StripeClient;
use App\Subscription\SubscriptionHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController {
  /**
   * @var ShoppingCart
   */
  private $cart;
  /**
   * @var StripeClient
   */
  private $stripeClient;
  /**
   * @var SubscriptionHelper
   */
  private $subscriptionHelper;

  public function __construct(
    ShoppingCart $cart,
    StripeClient $stripeClient,
    SubscriptionHelper $subscriptionHelper
  ){
    $this->cart = $cart;
    $this->stripeClient = $stripeClient;
    $this->subscriptionHelper = $subscriptionHelper;
  }

  /**
   * @Route("/cart/product/{slug}", name="order_add_product_to_cart", methods={"POST"})
   */
  public function addProductToCartAction(Product $product){
    $this->cart->addProduct($product);

    $this->addFlash('success', 'Product added!');

    return $this->redirectToRoute('order_checkout');
  }

  /**
   * @Route("/checkout", name="order_checkout", schemes={"https"})
   * @IsGranted("ROLE_USER")
   */
  public function checkoutAction(Request $request){
    $products = $this->cart->getProducts();
    $error  = false;

    if ($request->isMethod('POST')) {
      $token = $request->request->get('stripeToken');

      try {
        $this->chargeCustomer($token);
      } catch(\Stripe\Error\Card $e){
        $error = 'There was a problem charging the card '.$e->getMessage();
      }

      if(!$error) {
        $this->cart->emptyCart();
        $this->addFlash('success', 'Order Complete! Yay!');

        return $this->redirectToRoute('homepage');
      }
    }

    return $this->render('order/checkout.html.twig', array(
      'products' => $products,
      'cart' => $this->cart,
      'stripe_public_key' => $this->getParameter('stripe_public_key'),
      'error' => $error
    ));

  }
  /**
   * @Route("/cart/subscription/{planId}", name="order_add_subscription_to_cart")
   */
  public function addSubscriptionToCart($planId){
    $subscriptionHelper = $this->subscriptionHelper;
    $plan = $subscriptionHelper->findPlan($planId);

    if(!$planId){
      throw $this->createNotFoundException('Bad plan id!');
    }

    $this->cart->addSubscription($planId);

    return $this->redirectToRoute('order_checkout');
  }

  /**
   * @param $token
   * @throws \Stripe\Error\Card
   */
  private function chargeCustomer($token): void{
    /** @var User $user */
    $user = $this->getUser();
    $stripeClient = $this->stripeClient;

    if (!$user->getStripeCustomerId()) {
      $stripeClient->createCustomer($user, $token);
    } else {
      $stripeClient->updateCustomerCard($user, $token);
    }

    foreach ($this->cart->getProducts() as $product) {
      $stripeClient->createInvoiceItem(
        $product->getPrice() * 100,
        $user,
        $product->getName()
      );
    }

    if($this->cart->getSubscriptionPlan()){
      $stripeSubscription = $stripeClient->createSubscription($user, $this->cart->getSubscriptionPlan());
      $this->subscriptionHelper->addSubscriptionToUser($stripeSubscription, $user);
    } else {
      $stripeClient->createInvoice($user, true);
    }
  }
}