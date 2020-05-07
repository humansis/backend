<?php
namespace BeneficiaryBundle\OutputType\Factory;

use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Utils\InstitutionService;
use CommonBundle\Entity\Location;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\SerializationContext;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class AddressTypeFactory
{
    /** @var ArrayTransformerInterface $serializer */
    private $serializer;

    /**
     * InstitutionTypeFactory constructor.
     * @param ArrayTransformerInterface $serializer
     */
    public function __construct(ArrayTransformerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    private function expandLocation(Location $location): array
    {
        if ($location->getAdm4()) {
            return [
                'adm1' => $location->getAdm4()->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm4()->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm4()->getAdm3()->getId(),
                'adm4' => $location->getAdm4()->getId(),
            ];
        }
        if ($location->getAdm3()) {
            return [
                'adm1' => $location->getAdm3()->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm3()->getAdm2()->getId(),
                'adm3' => $location->getAdm3()->getId(),
                'adm4' => null,
            ];
        }
        if ($location->getAdm2()) {
            return [
                'adm1' => $location->getAdm2()->getAdm1()->getId(),
                'adm2' => $location->getAdm2()->getId(),
                'adm3' => null,
                'adm4' => null,
            ];
        }
        if ($location->getAdm1()) {
            return [
                'adm1' => $location->getAdm1()->getId(),
                'adm2' => null,
                'adm3' => null,
                'adm4' => null,
            ];
        }
        return [
            'adm1' => null,
            'adm2' => null,
            'adm3' => null,
            'adm4' => null,
        ];
    }

    public function build(Address $address): array
    {
        $array = $this->serializer->toArray($address, SerializationContext::create()->setGroups("Address")->setSerializeNull(true));
        if ($address->getLocation() !== null) {
            $array['location'] = $this->expandLocation($address->getLocation());
        }
        return $array;
    }
}
