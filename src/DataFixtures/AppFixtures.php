<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use App\DataFixtures\BaseFixture;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends BaseFixture {
  /**
   * @var UserPasswordEncoderInterface
   */
  private $encoder;

  public function __construct(UserPasswordEncoderInterface $encoder){
    $this->encoder = $encoder;
  }

  public function loadData(ObjectManager $manager) {
    $this->createMany(User::class, 11, function(User $user, $count){
      $user->setEmail('sleek_sheep'.$count.'@example.com');
      $password = $this->encoder->encodePassword($user, 'breakingbaad');
      $user->setPassword($password);
      $user->setRoles(["ROLE_USER"]);
    });

    $product1 = new Product();
    $product1->setName('Sheer Shears');
    $product1->setImageFilename('product_1.jpg');
    $product1->setPrice(9.5);
    $product1->setDescription($this->faker->realText(500,5));
    $manager->persist($product1);

    $product2 = new Product();
    $product2->setName('Wool Hauling Basket');
    $product2->setImageFilename('product_2.jpg');
    $product2->setPrice(19.5);
    $product2->setDescription($this->faker->realText(500,5));
    $manager->persist($product2);

    $product3 = new Product();
    $product3->setName('After-Shear (Fresh Cut Grass)');
    $product3->setImageFilename('product_3.jpg');
    $product3->setPrice(30);
    $product3->setDescription($this->faker->realText(500,5));
    $manager->persist($product3);

    $product4 = new Product();
    $product4->setName('After-Shear (Morning Dew)');
    $product4->setImageFilename('product_4.jpg');
    $product4->setPrice(35);
    $product4->setDescription($this->faker->realText(500,5));
    $manager->persist($product4);

    $product5 = new Product();
    $product5->setName('Shear Comb');
    $product5->setImageFilename('product_5.jpg');
    $product5->setPrice(50);
    $product5->setDescription($this->faker->realText(500,5));
    $manager->persist($product5);

    $product6 = new Product();
    $product6->setName('Shearly Conditioned');
    $product6->setImageFilename('product_6.png');
    $product6->setPrice(50);
    $product6->setDescription($this->faker->realText(500,5));
    $manager->persist($product6);

    $manager->flush();
  }
}
