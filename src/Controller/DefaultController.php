<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController {
  /**
   * @Route("/", name="homepage")
   */
  public function homepageAction(Request $request){
    $products = $this->getDoctrine()
      ->getRepository('App:Product')
      ->findAll();

    return $this->render('default/homepage.html.twig', array(
      'products' => $products,
    ));
  }
}