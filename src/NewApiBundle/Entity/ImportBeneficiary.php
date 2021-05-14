<?php
declare(strict_types=1);

namespace NewApiBundle\Entity;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\Mapping as ORM;
use UserBundle\Entity\User;

/**
 * This entity tracks source (import) of beneficiary being updated.
 *
 * @ORM\Entity()
 */
class ImportBeneficiary
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Import
     *
     * @ORM\ManyToOne(targetEntity="NewApiBundle\Entity\Import")
     */
    private $import;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary")
     */
    private $beneficiary;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="UserBundle\Entity\User")
     */
    private $createdBy;

    /**
     * @var \DateTimeInterface
     *
     * @ORM\Column(name="created_at", type="datetimetz", nullable=false)
     */
    private $createdAt;

    public function __construct(Import $import, Beneficiary $beneficiary, User $creator)
    {
        $this->import = $import;
        $this->beneficiary = $beneficiary;
        $this->createdBy = $creator;
        $this->createdAt = new \DateTime('now');
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

    /**
     * @return User
     */
    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
}
