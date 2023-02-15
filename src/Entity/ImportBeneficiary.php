<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\StandardizedPrimaryKey;

/**
 * This entity tracks source (import) of beneficiary being updated.
 */
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ImportBeneficiary
{
    use StandardizedPrimaryKey;
    use CreatedBy;
    use CreatedAt;

    /**
     * @var Import
     */
    #[ORM\ManyToOne(targetEntity: 'Entity\Import', inversedBy: 'importBeneficiaries')]
    private Import $import;

    /**
     * @var Beneficiary
     */
    #[ORM\ManyToOne(targetEntity: 'Entity\Beneficiary', cascade: ['persist'], inversedBy: 'importBeneficiaries')]
    #[ORM\JoinColumn(name: 'beneficiary_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Beneficiary $beneficiary;

    public function __construct(
        Import $import,
        Beneficiary $beneficiary,
        User $creator,
    ) {
        $this->import = $import;
        $this->beneficiary = $beneficiary;
        $this->createdBy = $creator;
    }

    public function getImport(): Import
    {
        return $this->import;
    }

    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }
}
