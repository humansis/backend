<?php
namespace DistributionBundle\OutputType\Factory;

use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\DistributionBeneficiaryRepository;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class AssistanceFactory
{
    /** @var DistributionBeneficiaryRepository */
    private $distributionBNFRepo;
    /** @var SerializerInterface */
    private $serializer;

    /**
     * AssistanceFactory constructor.
     * @param DistributionBeneficiaryRepository $distributionBNFRepo
     * @param SerializerInterface $serializer
     */
    public function __construct(DistributionBeneficiaryRepository $distributionBNFRepo, SerializerInterface $serializer)
    {
        $this->distributionBNFRepo = $distributionBNFRepo;
        $this->serializer = $serializer;
    }


    public function build(Assistance $assistance, array $distributionGroups): array
    {
        $distributionArray = [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'updated_on' => $assistance->getUpdatedOn(),
            'date_distribution' => $assistance->getDateDistribution(),
            'location' => $assistance->getLocation(),
            'project' => $assistance->getProject(),
            'selection_criteria' => $assistance->getSelectionCriteria(),
            'archived' => $assistance->getArchived(),
            'validated' => $assistance->getValidated(),
            'reporting_distribution' => $assistance->getReportingDistribution(),
            'type' => $assistance->getTargetType(),
            'assistance_type' => $assistance->getAssistanceType(),
            'target_type' => $assistance->getTargetType(),
            'commodities' => $assistance->getCommodities(),
            // 'distribution_beneficiaries' => $assistance->getDistributionBeneficiaries(),
            'completed' => $assistance->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($assistance),
        ];
        return $distributionArray;
    }
}
