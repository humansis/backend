<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Country;

class Country
{
    private $iso3;

    private $name;

    private $currency;

    private $adms = [];

    public function __construct(array $data)
    {
        if (!isset($data['iso3'])) {
            throw new \InvalidArgumentException("Invalid argument 1. It must contains attribute 'iso3'.");
        }
        if (!isset($data['name'])) {
            throw new \InvalidArgumentException("Invalid argument 1. It must contains attribute 'name'.");
        }
        if (!isset($data['currency'])) {
            throw new \InvalidArgumentException("Invalid argument 1. It must contains attribute 'currency'.");
        }
        if (!isset($data['adms'])) {
            throw new \InvalidArgumentException("Invalid argument 1. It must contains attribute 'adms'.");
        }
        if (4 !== count($data['adms'])) {
            throw new \InvalidArgumentException("Invalid argument 1. Attribute 'adms' does not contains complete list of names.");
        }

        $this->iso3 = $data['iso3'];
        $this->name = $data['name'];
        $this->currency = $data['currency'];
        $this->adms = $data['adms'];
    }

    public function getIso3(): string
    {
        return $this->iso3;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAdm1Name(): string
    {
        return $this->adms[0];
    }

    public function getAdm2Name(): string
    {
        return $this->adms[1];
    }

    public function getAdm3Name(): string
    {
        return $this->adms[2];
    }

    public function getAdm4Name(): string
    {
        return $this->adms[3];
    }

}
