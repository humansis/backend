<?php

declare(strict_types=1);

namespace Entity\Smartcard;

use Doctrine\ORM\Mapping as ORM;
use Entity\Project;
use Entity\Vendor;

/**
 * Read only entity.
 *
 * @ORM\Table(name="view_smartcard_preliminary_invoice")
 * @ORM\Entity(readOnly=true, repositoryClass="Repository\Smartcard\PreliminaryInvoiceRepository")
 */
class PreliminaryInvoice
{
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @ORM\Id
     */
    private $id;

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="Entity\Project")
     *
     */
    private $project;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="Entity\Vendor")
     */
    private $vendor;

    /**
     * @ORM\Column(name="value", type="decimal")
     */
    private $value;

    /**
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    /**
     * @var array
     *
     * @ORM\Column(name="purchase_ids", type="json")
     */
    private $purchaseIds;

    /**
     * @var int
     *
     * @ORM\Column(name="purchase_count", type="integer")
     */
    private $purchaseCount;

    /**
     * @return Project|null
     */
    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @return Vendor
     */
    public function getVendor(): Vendor
    {
        return $this->vendor;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return array
     */
    public function getPurchaseIds(): array
    {
        return $this->purchaseIds;
    }

    /**
     * @return int
     */
    public function getPurchaseCount(): int
    {
        return $this->purchaseCount;
    }
}
