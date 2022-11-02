<?php

declare(strict_types=1);

namespace Entity;

use Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use Entity\Helper\CreatedAt;
use Entity\Helper\CreatedBy;
use Entity\Helper\StandardizedPrimaryKey;
use Entity\User;

/**
 * This entity tracks source (import) of beneficiary being updated.
 *
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class ImportBeneficiary
{
    use StandardizedPrimaryKey;
    use CreatedBy;
    use CreatedAt;

    public function __construct(/**
         * @ORM\ManyToOne(targetEntity="Entity\Import", inversedBy="importBeneficiaries")
         */
        private Import $import, /**
         *
         * @ORM\ManyToOne(targetEntity="Entity\Beneficiary", inversedBy="importBeneficiaries", cascade={"persist"})
         * @ORM\JoinColumn(name="beneficiary_id", referencedColumnName="id", onDelete="CASCADE")
         */
        private Beneficiary $beneficiary,
        User $creator
    ) {
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
