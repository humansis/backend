<?php
namespace BeneficiaryBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class NationalIdType implements InputTypeInterface
{
    /**
     * @var string|null
     * @Assert\Length(max="255")
     * @Assert\Choice(choices=BeneficiaryBundle\Entity\NationalId::TYPE_ALL)
     */
    private $id_type;
    /**
     * @var string|null
     * @Assert\Length(max="255")
     */
    private $id_number;

    /**
     * @return string|null
     */
    public function getIdType(): ?string
    {
        return $this->id_type;
    }

    /**
     * @param string|null $id_type
     */
    public function setIdType(?string $id_type): void
    {
        $this->id_type = $id_type;
    }

    /**
     * @return string|null
     */
    public function getIdNumber(): ?string
    {
        return $this->id_number;
    }

    /**
     * @param string|null $id_number
     */
    public function setIdNumber(?string $id_number): void
    {
        $this->id_number = $id_number;
    }
}
