<?php
namespace BeneficiaryBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class LocationType implements InputTypeInterface
{
    /**
     * @var int|null
     * @Assert\GreaterThanOrEqual(0)
     */
    private $adm1;
    /**
     * @var int|null
     * @Assert\GreaterThanOrEqual(0)
     */
    private $adm2;
    /**
     * @var int|null
     * @Assert\GreaterThanOrEqual(0)
     */
    private $adm3;
    /**
     * @var int|null
     * @Assert\GreaterThanOrEqual(0)
     */
    private $adm4;

    /**
     * @return int|null
     */
    public function getAdm1(): ?int
    {
        return $this->adm1;
    }

    /**
     * @param int|null $adm1
     */
    public function setAdm1(?int $adm1): void
    {
        $this->adm1 = $adm1;
    }

    /**
     * @return int|null
     */
    public function getAdm2(): ?int
    {
        return $this->adm2;
    }

    /**
     * @param int|null $adm2
     */
    public function setAdm2(?int $adm2): void
    {
        $this->adm2 = $adm2;
    }

    /**
     * @return int|null
     */
    public function getAdm3(): ?int
    {
        return $this->adm3;
    }

    /**
     * @param int|null $adm3
     */
    public function setAdm3(?int $adm3): void
    {
        $this->adm3 = $adm3;
    }

    /**
     * @return int|null
     */
    public function getAdm4(): ?int
    {
        return $this->adm4;
    }

    /**
     * @param int|null $adm4
     */
    public function setAdm4(?int $adm4): void
    {
        $this->adm4 = $adm4;
    }

    public function getAdmByLevel(int $level): ?int
    {
        switch ($level) {
            case 1:
                return $this->getAdm1();
            case 2:
                return $this->getAdm2();
            case 3:
                return $this->getAdm3();
            case 4:
                return $this->getAdm4();
            default:
                return null;
        }
    }
}
