<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * NationalId
 *
 * @ORM\Table(name="national_id")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\NationalIdRepository")
 */
class NationalId
{
    const TYPE_NATIONAL_ID = 'national_id';
    const TYPE_PASSPORT = 'passport';
    const TYPE_FAMILY = 'family_registration';
    const TYPE_BIRTH_CERTIFICATE = 'birth_certificate';
    const TYPE_DRIVERS_LICENSE = 'drivers_license';
    const TYPE_CAMP_ID = 'camp_id';
    const TYPE_SOCIAL_SERVICE_ID = 'social_service_card';
    const TYPE_OTHER = 'other';

    const TYPE_ALL = [
        self::TYPE_NATIONAL_ID,
        self::TYPE_PASSPORT,
        self::TYPE_FAMILY,
        self::TYPE_BIRTH_CERTIFICATE,
        self::TYPE_DRIVERS_LICENSE,
        self::TYPE_CAMP_ID,
        self::TYPE_SOCIAL_SERVICE_ID,
        self::TYPE_OTHER,
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="id_number", type="string", length=255)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedDistribution"})
     */
    private $idNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="id_type", type="string", length=45)
     * @Groups({"FullHousehold", "SmallHousehold", "FullReceivers"})
     * @Assert\Choice(choices=BeneficiaryBundle\Entity\NationalId::TYPE_ALL)
     */
    private $idType;

    /**
     * @var Beneficiary
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Beneficiary", inversedBy="nationalIds")
     */
    private $beneficiary;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set idNumber.
     *
     * @param string $idNumber
     *
     * @return NationalId
     */
    public function setIdNumber($idNumber)
    {
        $this->idNumber = $idNumber;

        return $this;
    }

    /**
     * Get idNumber.
     *
     * @return string
     */
    public function getIdNumber()
    {
        return $this->idNumber;
    }

    /**
     * Set idType.
     *
     * @param string $idType
     *
     * @return NationalId
     */
    public function setIdType($idType)
    {
        $this->idType = $idType;

        return $this;
    }

    /**
     * Get idType.
     *
     * @return string
     */
    public function getIdType()
    {
        return $this->idType;
    }

    /**
     * Set beneficiary.
     *
     * @param \BeneficiaryBundle\Entity\Beneficiary|null $beneficiary
     *
     * @return NationalId
     */
    public function setBeneficiary(\BeneficiaryBundle\Entity\Beneficiary $beneficiary = null)
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return \BeneficiaryBundle\Entity\Beneficiary|null
     */
    public function getBeneficiary()
    {
        return $this->beneficiary;
    }
}
