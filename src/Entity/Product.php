<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CountryDependent;
use Entity\Helper\CreatedAt;
use Entity\Helper\LastModifiedAt;
use Entity\ProductCategory;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;
use Utils\ExportableInterface;

/**
 * Product
 *
 * @ORM\Table(name="product")
 * @ORM\Entity(repositoryClass="Repository\ProductRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Product implements ExportableInterface
{
    use CreatedAt;
    use LastModifiedAt;
    use CountryDependent;

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
     * @var ProductCategory|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\ProductCategory", inversedBy="products")
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
     * Get id.
     *
     * @return int
     */
    public function getId(): int
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
    public function setName(string $name): Product
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
    public function setArchived(bool $archived): Product
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     *
     * @return bool
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
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
    public function setUnit(?string $unit): Product
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
    public function setImage(string $image): Product
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     *
     * @return array
     */
    public function getMappedValueForExport(): array
    {
        return [
            'Name' => $this->getName(),
            'Unit' => $this->getUnit(),
        ];
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
     *
     * @return Product
     */
    public function setProductCategory(?ProductCategory $productCategory): Product
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
     *
     * @return Product
     */
    public function setUnitPrice(?float $unitPrice): Product
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
     *
     * @return Product
     */
    public function setCurrency(?string $currency): Product
    {
        $this->currency = $currency;

        return $this;
    }
}
