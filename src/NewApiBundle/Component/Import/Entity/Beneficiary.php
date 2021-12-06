<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Import\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\CreatedBy;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use UserBundle\Entity\User;

/**
 * This entity tracks source (import) of beneficiary being updated.
 *
 * @ORM\Entity()
 * @ORM\Table(name="import_beneficiary")
 */
class Beneficiary
{
    use StandardizedPrimaryKey;
    use CreatedAt;
    use CreatedBy;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Component\Import\Entity\Import", inversedBy="importBeneficiaries")
     */
    private $import;

    /**
     * @var \BeneficiaryBundle\Entity\Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary", inversedBy="importBeneficiaries", cascade={"persist"})
     * @ORM\JoinColumn(name="beneficiary_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $beneficiary;

    public function __construct(Import $import, Beneficiary $beneficiary, User $creator)
    {
        $this->import = $import;
        $this->beneficiary = $beneficiary;
        $this->createdBy = $creator;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @return Beneficiary
     */
    public function getBeneficiary(): Beneficiary
    {
        return $this->beneficiary;
    }
}
