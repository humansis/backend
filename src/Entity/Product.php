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
 */
#[ORM\Table(name: 'product')]
#[ORM\Entity(repositoryClass: 'Repository\ProductRepository')]
#[ORM\HasLifecycleCallbacks]
class Product implements ExportableInterface
{
    use CreatedAt;
    use LastModifiedAt;
    use CountryDependent;

    #[SymfonyGroups(['FullProduct', 'ValidatedAssistance', 'FullVoucher'])]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id;

    #[SymfonyGroups(['FullProduct', 'ValidatedAssistance'])]
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private ?string $name = null;

    #[SymfonyGroups(['FullProduct'])]
    #[ORM\Column(name: 'unit', type: 'string', length: 255, nullable: true)]
    private ?string $unit = null;

    #[SymfonyGroups(['FullProduct', 'ValidatedAssistance'])]
    #[ORM\Column(name: 'image', type: 'string', length: 255)]
    private ?string $image = null;

    #[SymfonyGroups(['FullProduct'])]
    #[ORM\Column(name: 'archived', type: 'boolean')]
    private ?bool $archived = null;

    #[ORM\ManyToOne(targetEntity: 'Entity\ProductCategory', inversedBy: 'products')]
    private ?\Entity\ProductCategory $productCategory = null;

    #[ORM\Column(name: 'unit_price', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?float $unitPrice = null;

    #[ORM\Column(name: 'currency', type: 'string', length: 3, nullable: true)]
    private ?string $currency = null;

    /**
     * Get id.
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     *
     */
    public function setName(string $name): Product
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set archived.
     *
     *
     */
    public function setArchived(bool $archived): Product
    {
        $this->archived = $archived;

        return $this;
    }

    /**
     * Get archived.
     */
    public function getArchived(): bool
    {
        return $this->archived;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set unit.
     *
     *
     */
    public function setUnit(?string $unit): Product
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Set image.
     *
     *
     */
    public function setImage(string $image): Product
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     */
    public function getImage(): string
    {
        return $this->image;
    }

    /**
     * Returns an array representation of this class in order to prepare the export
     */
    public function getMappedValueForExport(): array
    {
        return [
            'Name' => $this->getName(),
            'Unit' => $this->getUnit(),
        ];
    }

    public function getProductCategory(): ?ProductCategory
    {
        return $this->productCategory;
    }

    public function setProductCategory(?ProductCategory $productCategory): Product
    {
        $this->productCategory = $productCategory;

        return $this;
    }

    public function getUnitPrice(): ?float
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(?float $unitPrice): Product
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): Product
    {
        $this->currency = $currency;

        return $this;
    }
}
