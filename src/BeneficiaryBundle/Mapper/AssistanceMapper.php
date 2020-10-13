<?php

namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Repository\DistributionBeneficiaryRepository;

class AssistanceMapper
{
    /** @var BeneficiaryMapper */
    private $beneficiaryMapper;
    /** @var DistributionBeneficiaryRepository */
    private $distributionBNFRepo;

    /**
     * AssistanceMapper constructor.
     *
     * @param BeneficiaryMapper $beneficiaryMapper
     * @param DistributionBeneficiaryRepository $distributionBNFRepo
     */
    public function __construct(
        BeneficiaryMapper $beneficiaryMapper,
        DistributionBeneficiaryRepository $distributionBNFRepo
    ) {
        $this->beneficiaryMapper = $beneficiaryMapper;
        $this->distributionBNFRepo = $distributionBNFRepo;
    }

    public function toMinimalArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
        ];
    }

    public function toMinimalArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toMinimalArray($assistance);
        }
    }

    public function toBeneficiaryOnlyArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        /** @var AbstractBeneficiary[] $bnfs */
        $bnfs = $assistance->getDistributionBeneficiaries()->map(
            function (DistributionBeneficiary $db) {
                return $db->getBeneficiary();
            }
        );
        $dbs = [];
        foreach ($assistance->getDistributionBeneficiaries() as $distributionBeneficiary) {
            $dbs[] = [
                'beneficiary' => $this->beneficiaryMapper->toMinimalArrays($bnfs),
            ];
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'beneficiaries' => $this->beneficiaryMapper->toMinimalArrays($bnfs),
            'distribution_beneficiaries' => $dbs,
        ];
    }

    public function toBeneficiaryOnlyArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toBeneficiaryOnlyArray($assistance);
        }
    }

    public function toFullArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        $assistanceArray = [
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

        return $assistanceArray;
    }

    public function toFullArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toFullArray($assistance);
        }
    }

    /**
     * @param Assistance|null $assistance
     *
     * @return array
     * @deprecated this is too big so dont use it
     */
    public function toOldMobileArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }
        /** @var AbstractBeneficiary[] $bnfs */
        $bnfs = [];
        foreach ($assistance->getDistributionBeneficiaries() as $db)
        {
            if ($db->getBeneficiary() instanceof Beneficiary
                && !$db->getRemoved()
                && !$db->getBeneficiary()->getArchived()
            ) {
                $bnfs[] = $db->getBeneficiary();
            }
        };

        $assistanceArray = [
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
            'distribution_beneficiaries' => $this->beneficiaryMapper->toOldMobileArrays($bnfs),
            'completed' => $assistance->getCompleted(),
            'beneficiaries_count' => $this->distributionBNFRepo->countActive($assistance),
        ];

        return $assistanceArray;
    }

    /**
     * @param iterable $assistances
     *
     * @return iterable
     * @deprecated this is too big so dont use it
     */
    public function toOldMobileArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toOldMobileArray($assistance);
        }
    }
}
