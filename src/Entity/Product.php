<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProductRepository")
 */
class Product {
  /**
   * @ORM\Id()
   * @ORM\GeneratedValue()
   * @ORM\Column(type="integer")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=255)
   */
  private $name;

  /**
   * @ORM\Column(type="string", length=255)
   * @Gedmo\Slug(fields={"name"})
   */
  private $slug;

  /**
   * @ORM\Column(type="decimal", precision=10, scale=2)
   */
  private $price;

  /**
   * @ORM\Column(type="text")
   */
  private $description;

  /**
   * @ORM\Column(type="string", length=255)
   */
  private $imageFilename;

  public function getId(): ?int {
    return $this->id;
  }

  public function getName(): ?string {
    return $this->name;
  }

  public function setName(string $name): self {
    $this->name = $name;

    return $this;
  }

  public function getSlug(): ?string {
    return $this->slug;
  }

  public function setSlug(string $slug): self {
    $this->slug = $slug;

    return $this;
  }

  public function getPrice() {
    return $this->price;
  }

  public function setPrice($price): self {
    $this->price = $price;

    return $this;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function setDescription(string $description): self {
    $this->description = $description;

    return $this;
  }

  public function getImageFilename(): ?string {
    return $this->imageFilename;
  }

  public function setImageFilename(string $imageFilename): self {
    $this->imageFilename = $imageFilename;

    return $this;
  }
}
