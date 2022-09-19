<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use DBAL\NationalIdTypeEnum;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\NationalIdType;
use InputType\Beneficiary\NationalIdCardInputType;

/**
 * NationalId
 *
 * @ORM\Table(name="national_id", indexes={
 *     @ORM\Index(name="duplicity_check_idx", columns={"id_type", "id_number"})
 * })
 * @ORM\Entity(repositoryClass="Repository\NationalIdRepository")
 */
class NationalId
{
    use StandardizedPrimaryKey;
    use EnumTrait;

    /**
     * @var string
     *
     * @ORM\Column(name="id_number", type="string", length=255)
     */
    private $idNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="id_type", type="enum_national_id_type")
     */
    private $idType;

    /**
     * @var int
     *
     * @ORM\Column(name="priority", type="integer")
     */
    private $priority;

    /**
     * @var Person
     *
     * @ORM\ManyToOne(targetEntity="Entity\Person", inversedBy="nationalIds")
     */
    private $person;

    /**
     * @param string $idNumber
     */
    public function __construct()
    {
        $this->priority = 1;
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
     * @param Person|null $person
     *
     * @return NationalId
     */
    public function setPerson(Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return Person|null
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @param NationalIdCardInputType $inputType
     *
     * @return NationalId
     */
    public static function fromNationalIdInputType(NationalIdCardInputType $inputType): NationalId
    {
        $nationalId = new NationalId();
        $nationalId->setIdType($inputType->getType());
        $nationalId->setIdNumber($inputType->getNumber());
        $nationalId->setPriority($inputType->getPriority());
        return $nationalId;
    }
}
