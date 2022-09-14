<?php declare(strict_types=1);

namespace BeneficiaryBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use NewApiBundle\DBAL\NationalIdTypeEnum;
use NewApiBundle\Entity\AbstractEntity;
use NewApiBundle\Entity\Helper\EnumTrait;
use NewApiBundle\Enum\NationalIdType;
use Symfony\Component\Serializer\Annotation\Groups as SymfonyGroups;

/**
 * NationalId
 *
 * @ORM\Table(name="national_id", indexes={
 *     @ORM\Index(name="duplicity_check_idx", columns={"id_type", "id_number"})
 * })
 * @ORM\Entity(repositoryClass="BeneficiaryBundle\Repository\NationalIdRepository")
 */
class NationalId extends AbstractEntity
{
    use EnumTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="id_number", type="string", length=255)
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "ValidatedAssistance", "FullInstitution"})
     */
    private $idNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="id_type", type="enum_national_id_type")
     * @SymfonyGroups({"FullHousehold", "SmallHousehold", "FullReceivers", "FullInstitution"})
     */
    private $idType;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="BeneficiaryBundle\Entity\Person", inversedBy="nationalIds")
     */
    private $person;

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
        self::validateValue('idType', NationalIdType::class, $idType);
        $this->idType = NationalIdTypeEnum::valueToDB($idType);

        return $this;
    }

    /**
     * Get idType.
     *
     * @return string
     */
    public function getIdType()
    {
        return NationalIdTypeEnum::valueFromDB($this->idType);
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
