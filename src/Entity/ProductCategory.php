<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Enum\ImportState;
use Enum\ProductCategoryType;
use Entity\Product;
use InvalidArgumentException;

/**
 * Product
 *
 * @ORM\Table(name="product_category")
 * @ORM\Entity(repositoryClass="Repository\ProductCategoryRepository")
 */
class ProductCategory
{
    /**
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="image", type="text", nullable=true)
     */
    private ?string $image = null;

    /**
     * @ORM\Column(name="archived", type="boolean", nullable=false)
     */
    private bool $archived = false;

    /**
     * @var Collection|Product[]
     *
     * @ORM\OneToMany(targetEntity="Entity\Product", mappedBy="productCategory")
     */
    private \Doctrine\Common\Collections\Collection|array $products;

    public function __construct(/**
         * @ORM\Column(name="name", type="string", nullable=false)
         */
        private string $name, /**
         * @ORM\Column(name="type", type="enum_product_category_type", nullable=false)
         */
        private string $type
    ) {
        $this->products = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->products;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        if (!in_array($type, ProductCategoryType::values())) {
            throw new InvalidArgumentException('Invalid argument. ' . $type . ' is not valid Product category type');
        }

        $this->type = $type;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function setArchived(bool $archived): void
    {
        $this->archived = $archived;
    }
}
