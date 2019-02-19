<?php

namespace VoucherBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * Product
 *
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\ProductRepository")
 */
class Product
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullProduct"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"FullProduct"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", length=255)
     * @Groups({"FullProduct"})
     */
    private $unit;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255)
     * @Groups({"FullProduct"})
     */
    private $image;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean")
     * @Groups({"FullProduct"})
     */
    private $archived;

    /**
     * @ORM\ManyToMany(targetEntity="VoucherBundle\Entity\Booklet", mappedBy="product")
     */
    private $booklets;

     /**
     * @ORM\OneToMany(targetEntity="\VoucherBundle\Entity\ProductQuantity", mappedBy="product")
     */
    private $productQuantities;

    public function __construct()
    {
        $this->booklets = new ArrayCollection();
    }


    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Product
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set archived.
     *
     * @param bool $archived
     *
     * @return Product
     */
    public function setArchived($archived)
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived()
    {
        return $this->archived;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set unit.
     *
     * @param string $unit
     *
     * @return Product
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set image.
     *
     * @param string $image
     *
     * @return Product
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return Collection|Booklet[]
     */
    public function getBooklets(): Collection
    {
        return $this->booklets;
    }

    public function addBooklet(Booklet $booklet): self
    {
        if (!$this->booklets->contains($booklet)) {
            $this->booklets[] = $booklet;
            $booklet->addProduct($this);
        }

        return $this;
    }

    public function removeBooklet(Booklet $booklet): self
    {
        if ($this->booklets->contains($booklet)) {
            $this->booklets->removeElement($booklet);
            $booklet->removeProduct($this);
        }

        return $this;
    }

     /**
     * Get productQuantities.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProductQuantities()
    {
        return $this->productQuantities;
    }

    /**
     * Set productQuantities.
     *
     * @param $collection
     *
     * @return Product
     */
    public function setProductQuantities(\Doctrine\Common\Collections\Collection $collection = null)
    {
        $this->productQuantities = $collection;

        return $this;
    }
}
