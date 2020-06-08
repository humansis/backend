<?php
namespace DistributionBundle\OutputType\Factory;

use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Repository\DistributionBeneficiaryRepository;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\SerializationContext;

class DistributionDataFactory
{
    /** @var DistributionBeneficiaryRepository */
    private $distributionBNFRepo;
    /** @var ArrayTransformerInterface */
    private $serializer;

    /**
     * DistributionDataFactory constructor.
     * @param DistributionBeneficiaryRepository $distributionBNFRepo
     * @param ArrayTransformerInterface $serializer
     */
    public function __construct(DistributionBeneficiaryRepository $distributionBNFRepo, ArrayTransformerInterface $serializer)
    {
        $this->distributionBNFRepo = $distributionBNFRepo;
        $this->serializer = $serializer;
    }


    public function build(DistributionData $distributionData, array $distributionGroups): array
    {
        $distributionArray = $this->serializer
            ->toArray(
                $distributionData,
                SerializationContext::create()->setSerializeNull(true)->setGroups($distributionGroups)
            );
        $distributionArray['beneficiaries_count'] = $this->distributionBNFRepo->countActive($distributionData);
        return $distributionArray;
    }
}
