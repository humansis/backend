<?php

namespace InputType\Deprecated;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class LocationType implements InputTypeInterface
{
    #[Assert\GreaterThanOrEqual(0)]
    private ?int $adm1 = null;

    #[Assert\GreaterThanOrEqual(0)]
    private ?int $adm2 = null;

    #[Assert\GreaterThanOrEqual(0)]
    private ?int $adm3 = null;

    #[Assert\GreaterThanOrEqual(0)]
    private ?int $adm4 = null;

    public function getAdm1(): ?int
    {
        return $this->adm1;
    }

    public function setAdm1(?int $adm1): void
    {
        $this->adm1 = $adm1;
    }

    public function getAdm2(): ?int
    {
        return $this->adm2;
    }

    public function setAdm2(?int $adm2): void
    {
        $this->adm2 = $adm2;
    }

    public function getAdm3(): ?int
    {
        return $this->adm3;
    }

    public function setAdm3(?int $adm3): void
    {
        $this->adm3 = $adm3;
    }

    public function getAdm4(): ?int
    {
        return $this->adm4;
    }

    public function setAdm4(?int $adm4): void
    {
        $this->adm4 = $adm4;
    }

    public function getAdmByLevel(int $level): ?int
    {
        return match ($level) {
            1 => $this->getAdm1(),
            2 => $this->getAdm2(),
            3 => $this->getAdm3(),
            4 => $this->getAdm4(),
            default => null,
        };
    }
}
