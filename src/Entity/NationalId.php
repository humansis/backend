<?php

declare(strict_types=1);

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use DBAL\NationalIdTypeEnum;
use Entity\Helper\EnumTrait;
use Entity\Helper\StandardizedPrimaryKey;
use Enum\NationalIdType;
use Exception;
use InputType\Beneficiary\NationalIdCardInputType;

/**
 * NationalId
 */
#[ORM\Table(name: 'national_id')]
#[ORM\Index(columns: ['id_type', 'id_number'], name: 'duplicity_check_idx')]
#[ORM\Entity(repositoryClass: 'Repository\NationalIdRepository')]
class NationalId
{
    use StandardizedPrimaryKey;
    use EnumTrait;

    #[ORM\Column(name: 'id_number', type: 'string', length: 255)]
    private string $idNumber;

    /**
     * @var string
     */
    #[ORM\Column(name: 'id_type', type: 'enum_national_id_type')]
    private $idType;

    #[ORM\Column(name: 'priority', type: 'integer')]
    private int $priority;

    #[ORM\ManyToOne(targetEntity: 'Entity\Person', inversedBy: 'nationalIds')]
    private ?\Entity\Person $person = null;

    /**
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
    public function setIdNumber(string $idNumber): NationalId
    {
        $this->idNumber = $idNumber;

        return $this;
    }

    /**
     * Get idNumber.
     *
     * @return string
     */
    public function getIdNumber(): string
    {
        return $this->idNumber;
    }

    /**
     * Set idType.
     *
     * @param string $idType
     *
     * @return NationalId
     * @throws Exception
     */
    public function setIdType(string $idType): NationalId
    {
        self::validateValue('idType', NationalIdType::class, $idType);
        $this->idType = NationalIdTypeEnum::valueToDB($idType);

        return $this;
    }

    /**
     * Get idType.
     *
     * @return string
     * @throws Exception
     */
    public function getIdType(): string
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
    public function setPerson(Person $person = null): NationalId
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get beneficiary.
     *
     * @return Person|null
     */
    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public static function fromNationalIdInputType(NationalIdCardInputType $inputType): NationalId
    {
        $nationalId = new NationalId();
        $nationalId->setIdType($inputType->getType());
        $nationalId->setIdNumber($inputType->getNumber());
        $nationalId->setPriority($inputType->getPriority());

        return $nationalId;
    }
}
