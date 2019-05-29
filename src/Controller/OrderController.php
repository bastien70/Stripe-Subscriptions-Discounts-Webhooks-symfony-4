<?php


namespace App\Controller;


use App\Entity\Product;
use App\Entity\User;
use App\Store\ShoppingCart;
use App\StripeClient;
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

  public function __construct(ShoppingCart $cart, StripeClient $stripeClient){
    $this->cart = $cart;
    $this->stripeClient = $stripeClient;
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

    $stripeClient->createInvoice($user, true);
  }
}