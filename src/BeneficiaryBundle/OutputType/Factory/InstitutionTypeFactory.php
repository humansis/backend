<?php
namespace BeneficiaryBundle\OutputType\Factory;

use BeneficiaryBundle\Entity\Institution;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\SerializationContext;

class InstitutionTypeFactory
{
    /** @var ArrayTransformerInterface $serializer */
    private $serializer;
    /** @var AddressTypeFactory */
    private $addressTypeFactory;

    /**
     * InstitutionTypeFactory constructor.
     * @param ArrayTransformerInterface $serializer
     * @param AddressTypeFactory $addressTypeFactory
     */
    public function __construct(ArrayTransformerInterface $serializer, AddressTypeFactory $addressTypeFactory)
    {
        $this->serializer = $serializer;
        $this->addressTypeFactory = $addressTypeFactory;
    }

    public function build(Institution $institution): array
    {
        $institutionArray = $this->serializer->toArray($institution, SerializationContext::create()->setGroups("FullInstitution")->setSerializeNull(true));
        if ($institution->getAddress() !== null) {
            $institutionArray['address'] = $this->addressTypeFactory->build($institution->getAddress());
        }

        return $institutionArray;
    }

    public function buildList(array $array): array
    {
        [$count, $list] = $array;
        $outputTypes = [];

        foreach ($list as $institution) {
            $outputTypes[] = $this->build($institution);
        }

        return [
            $count,
            $outputTypes,
        ];
    }
}
