<?php

declare(strict_types=1);

namespace Entity\Smartcard;

use Doctrine\ORM\Mapping as ORM;
use Entity\Project;
use Entity\Vendor;

/**
 * Read only entity.
 */
#[ORM\Table(name: 'view_smartcard_preliminary_invoice')]
#[ORM\Entity(repositoryClass: 'Repository\Smartcard\PreliminaryInvoiceRepository', readOnly: true)]
class PreliminaryInvoice
{
    #[ORM\Column(type: 'string')]
    #[ORM\Id]
    private string $id;

    #[ORM\ManyToOne(targetEntity: 'Entity\Project')]
    private ?\Entity\Project $project = null;

    #[ORM\ManyToOne(targetEntity: 'Entity\Vendor')]
    private \Entity\Vendor $vendor;

    #[ORM\Column(name: 'value', type: 'decimal')]
    private $value;

    #[ORM\Column(name: 'currency', type: 'string')]
    private $currency;

    #[ORM\Column(name: 'purchase_ids', type: 'json')]
    private array $purchaseIds;

    #[ORM\Column(name: 'purchase_count', type: 'integer')]
    private int $purchaseCount;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_redeemable', type: 'boolean')]
    private $isRedeemable;

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPurchaseIds(): array
    {
        return $this->purchaseIds;
    }

    public function getPurchaseCount(): int
    {
        return $this->purchaseCount;
    }

    public function isRedeemable(): bool
    {
        return $this->isRedeemable;
    }
}
