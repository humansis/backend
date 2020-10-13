<?php
namespace DistributionBundle\OutputType\Factory;

use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Repository\DistributionBeneficiaryRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DistributionDataFactory
{
    /** @var DistributionBeneficiaryRepository */
    private $distributionBNFRepo;
    /** @var SerializerInterface */
    private $serializer;

    /**
     * DistributionDataFactory constructor.
     * @param DistributionBeneficiaryRepository $distributionBNFRepo
     * @param SerializerInterface $serializer
     */
    public function __construct(DistributionBeneficiaryRepository $distributionBNFRepo, SerializerInterface $serializer)
    {
        $this->distributionBNFRepo = $distributionBNFRepo;
        $this->serializer = $serializer;
    }


    public function build(DistributionData $distributionData, array $distributionGroups): array
    {
        $distributionArray = [
            'id' => $distributionData->getId(),
            'name' => $distributionData->getName(),
            'updated_on' => $distributionData->getUpdatedOn(),
            'date_distribution' => $distributionData->getDateDistribution(),
            'location' => $distributionData->getLocation(),
            'project' => $distributionData->getProject(),
            'selection_criteria' => $distributionData->getSelectionCriteria(),
            'archived' => $distributionData->getArchived(),
            'validated' => $distributionData->getValidated(),
            'reporting_distribution' => $distributionData->getReportingDistribution(),
            'type' => $distributionData->getType(),
            'commodities' => $distributionData->getCommodities(),
            // 'distribution_beneficiaries' => $distributionData->getDistributionBeneficiaries(),
            'completed' => $distributionData->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($distributionData),
        ];
        return $distributionArray;
    }
}
