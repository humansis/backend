<?php

namespace VoucherBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\AbstractEntity;
use NewApiBundle\Entity\Helper\LastModifiedAt;
use NewApiBundle\Entity\ProductCategory;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use CommonBundle\Utils\ExportableInterface;

/**
 * Product
 *
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="VoucherBundle\Repository\ProductRepository")
 */
class Product extends AbstractEntity implements ExportableInterface
{
    use LastModifiedAt;

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
     * @var float|null
     *
     * @ORM\Column(name="unit_price", type="decimal", type="decimal", precision=10, scale=2, nullable=true)
     */
    private $unitPrice;

    /**
     * @var string|null
     *
     * @ORM\Column(name="currency", type="string", length=3, nullable=true)
     */
    private $currency;

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
    public function setProductCategory(?ProductCategory $productCategory): self
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    /**
     * @return float|null
     */
    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    /**
     * @param float|null $unitPrice
     */
    public function setUnitPrice(?float $unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     */
    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

}
