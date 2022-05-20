<?php declare(strict_types=1);

namespace NewApiBundle\Entity\Smartcard;

use Doctrine\ORM\Mapping as ORM;
use ProjectBundle\Entity\Project;
use VoucherBundle\Entity\Vendor;

/**
 * Read only entity.
 *
 * @ORM\Table(name="view_smartcard_preliminary_invoice")
 * @ORM\Entity(readOnly=true, repositoryClass="NewApiBundle\Repository\Smartcard\PreliminaryInvoiceRepository")
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
     * @ORM\ManyToOne(targetEntity="ProjectBundle\Entity\Project")
     *
     */
    private $project;

    /**
     * @var Vendor
     *
     * @ORM\ManyToOne(targetEntity="VoucherBundle\Entity\Vendor")
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
     * @var string
     *
     * @ORM\Column(name="purchase_ids", type="string")
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
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return string[]
     */
    public function getPurchaseIds(): array
    {
        return explode(',', $this->purchaseIds);
    }

    /**
     * @return int
     */
    public function getPurchaseCount(): int
    {
        return $this->purchaseCount;
    }


}
