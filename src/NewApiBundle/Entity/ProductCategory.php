<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Enum\ImportState;
use VoucherBundle\Entity\Product;

/**
 * Product
 *
 * @ORM\Table(name="product_category")
 * @ORM\Entity(repositoryClass="NewApiBundle\Repository\ProductCategoryRepository")
 */
class ProductCategory
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="enum_product_category_type", nullable=false)
     */
    private $type;

    /**
     * @var string|null
     *
     * @ORM\Column(name="image", type="text", nullable=true)
     */
    private $image;

    /**
     * @var Collection|Product[]
     *
     * @ORM\OneToMany(targetEntity="VoucherBundle\Entity\Product", mappedBy="productCategory")
     */
    private $products;

    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->products = new ArrayCollection();
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Collection|Product[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        if (!in_array($type, ImportState::values())) {
            throw new \InvalidArgumentException('Invalid argument. '.$type.' is not valid Product category type');
        }

        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     */
    public function setImage(?string $image): void
    {
        $this->image = $image;
    }
}
