<?php declare(strict_types=1);

namespace NewApiBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\Entity\Helper\CreatedAt;
use NewApiBundle\Entity\Helper\CreatedBy;
use NewApiBundle\Entity\Helper\StandardizedPrimaryKey;
use UserBundle\Entity\User;

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

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import", inversedBy="importBeneficiaries")
     */
    private $import;

    /**
     * @var Beneficiary
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
