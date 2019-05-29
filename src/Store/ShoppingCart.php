<?php


namespace App\Store;


use App\Entity\Product;
use App\Subscription\SubscriptionHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ShoppingCart {
  const CART_PRODUCTS_KEY = '_shopping_cart.products';
  const CART_PLAN_KEY = '_shopping_cart.subscription_plan';
  const CART_COUPON_CODE_KEY = '_shopping_cart.coupon_code';
  const CART_COUPON_VALUE_KEY = '_shopping_cart.coupon_value';

  private $session;
  private $em;

  private $products;

  public function __construct(
    SessionInterface $session,
    EntityManagerInterface $em
  ){
    $this->session = $session;
    $this->em = $em;
  }

  public function addProduct(Product $product){
    $products = $this->getProducts();

    if (!in_array($product, $products)) {
      $products[] = $product;
    }

    $this->updateProducts($products);
  }

  /**
   * @return Product[]
   */
  public function getProducts(){
    if ($this->products === null) {
      $productRepo = $this->em->getRepository('App:Product');
      $ids = $this->session->get(self::CART_PRODUCTS_KEY, []);
      $products = [];
      foreach ($ids as $id) {
        $product = $productRepo->find($id);

        // in case a product becomes deleted
        if ($product) {
          $products[] = $product;
        }
      }

      $this->products = $products;
    }

    return $this->products;
  }

  public function getTotal(){
    $total = 0;
    foreach ($this->getProducts() as $product) {
      $total += $product->getPrice();
    }

    return $total;
  }

  public function emptyCart(){
    $this->updateProducts([]);
  }

  /**
   * @param Product[] $products
   */
  private function updateProducts(array $products){
    $this->products = $products;

    $ids = array_map(function(Product $item) {
      return $item->getId();
    }, $products);

    $this->session->set(self::CART_PRODUCTS_KEY, $ids);
  }
}