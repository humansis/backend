<?php

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * NationalId
 *
 * @ORM\Table(name="national_id")
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\NationalIdRepository")
 */
class NationalId
{
    const TYPE_NATIONAL_ID = 'National ID';
    const TYPE_PASSPORT = 'Passport';
    const TYPE_FAMILY = 'Family Registration';
    const TYPE_BIRTH_CERTIFICATE = 'Birth Certificate';
    const TYPE_DRIVERS_LICENSE = 'Driver’s License';
    const TYPE_CAMP_ID = 'Camp ID';
    const TYPE_SOCIAL_SERVICE_ID = 'Social Service Card';
    const TYPE_OTHER = 'Other';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="id_number", type="string", length=255)
     *
     */
    private $idNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="id_type", type="string", length=45)
     *
     * @Assert\Choice(callback={"BeneficiaryBundle\Entity\NationalId", "types"})
     */
    private $idType;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Person", inversedBy="nationalIds")
     */
    private $person;

    public static function types()
    {
        return [
            self::TYPE_NATIONAL_ID,
            self::TYPE_PASSPORT,
            self::TYPE_FAMILY,
            self::TYPE_BIRTH_CERTIFICATE,
            self::TYPE_DRIVERS_LICENSE,
            self::TYPE_CAMP_ID,
            self::TYPE_SOCIAL_SERVICE_ID,
            self::TYPE_OTHER,
        ];
    }

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
     * @param \BeneficiaryBundle\Entity\Person|null $person
     *
     * @return NationalId
     */
    public function setPerson(\BeneficiaryBundle\Entity\Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return \BeneficiaryBundle\Entity\Person|null
     */
    public function getPerson()
    {
        return $this->person;
    }
}
