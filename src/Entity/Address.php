<?php

namespace Entity;

use Entity\Helper\StandardizedPrimaryKey;
use Doctrine\ORM\Mapping as ORM;

/**
 * Address
 */
#[ORM\Table(name: 'address')]
#[ORM\Entity(repositoryClass: 'Repository\AddressRepository')]
class Address
{
    use StandardizedPrimaryKey;

    #[ORM\Column(name: 'number', type: 'string', length: 45, nullable: true)]
    private ?string $number = null;

    #[ORM\Column(name: 'street', type: 'string', length: 255, nullable: true)]
    private string | null $street;

    #[ORM\Column(name: 'postcode', type: 'string', length: 45, nullable: true)]
    private string | null $postcode;

    #[ORM\ManyToOne(targetEntity: 'Entity\Location')]
    private Location | null $location;

    public static function create(?string $street, ?string $number, ?string $postCode, ?Location $location = null): self
    {
        $address = new Address();
        $address->setNumber($number)
            ->setStreet($street)
            ->setPostcode($postCode)
            ->setLocation($location);

        return $address;
    }

    public function setNumber(string | null $number = null): static
    {
        $this->number = $number;

        return $this;
    }

    public function getNumber(): string | null
    {
        return $this->number;
    }

    public function setStreet(string | null $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getStreet(): string | null
    {
        return $this->street;
    }

    public function setPostcode(string | null $postcode): static
    {
        $this->postcode = $postcode;

        return $this;
    }

    public function getPostcode(): string | null
    {
        return $this->postcode;
    }

    public function setLocation(Location | null $location = null): static
    {
        $this->location = $location;

        return $this;
    }

    public function getLocation(): Location | null
    {
        return $this->location;
    }

    public function equals(self $address): bool
    {
        if ($address->number !== $this->number) {
            return false;
        }
        if ($address->street !== $this->street) {
            return false;
        }
        if ($address->postcode !== $this->postcode) {
            return false;
        }
        if ($address->location !== $this->location) {
            return false;
        }

        return true;
    }
}
