<?php


namespace App\Controller;

use App\Entity\Product;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController {
  /**
   * @Route("/products/{slug}", name="product_show")
   */
  public function showAction(Product $product){
    return $this->render('product/show.html.twig', array(
      'product' => $product
    ));
  }

  /**
   * @Route("/pricing", name="pricing_show")
   */
  public function pricingAction() {
    return $this->render('product/pricing.html.twig', array(
    ));
  }
}