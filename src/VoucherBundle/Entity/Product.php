<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\ProductCategory;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use CommonBundle\Utils\ExportableInterface;

/**
 * Product
 *
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\ProductRepository")
 */
class Product implements ExportableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @SymfonyGroups({"FullProduct", "ValidatedAssistance", "FullVoucher"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @SymfonyGroups({"FullProduct", "ValidatedAssistance"})
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(name="unit", type="string", length=255, nullable=true)
     * @SymfonyGroups({"FullProduct"})
     */
    private $unit;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255)
     * @SymfonyGroups({"FullProduct", "ValidatedAssistance"})
     */
    private $image;

    /**
     * @var bool
     *
     * @ORM\Column(name="archived", type="boolean")
     * @SymfonyGroups({"FullProduct"})
     */
    private $archived;

    /**
     * @var string
     *
     * @ORM\Column(name="countryISO3", type="string", length=3)
     * @SymfonyGroups({"FullProduct"})
     */
    private $countryISO3;

    /**
     * @var ProductCategory|null
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\ProductCategory", inversedBy="products")
     */
    private $productCategory;

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
     * @param string|null $unit
     *
     * @return Product
     */
    public function setUnit(?string $unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     *
     * @return string|null
     */
    public function getUnit(): ?string
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
     * Set countryISO3.
     *
     * @param string $countryISO3
     *
     * @return Product
     */
    public function setCountryISO3($countryISO3)
    {
        $this->countryISO3 = $countryISO3;

        return $this;
    }

    /**
     * Get countryISO3.
     *
     * @return string
     */
    public function getCountryISO3()
    {
        return $this->countryISO3;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     * @return array
     */
    public function getMappedValueForExport(): array
    {

        $finalArray = [
            'Name' => $this->getName(),
            'Unit' => $this->getUnit(),
        ];

        return $finalArray;
    }

    /**
     * @return ProductCategory|null
     */
    public function getProductCategory(): ?ProductCategory
    {
        return $this->productCategory;
    }

    /**
     * @param ProductCategory|null $productCategory
     */
    public function setProductCategory(?ProductCategory $productCategory): void
    {
        $this->productCategory = $productCategory;
    }

}
