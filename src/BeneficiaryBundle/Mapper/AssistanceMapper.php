<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\DistributionBeneficiary;
use DistributionBundle\Entity\Assistance;

class AssistanceMapper
{
    /** @var BeneficiaryMapper */
    private $beneficiaryMapper;

    /**
     * AssistanceMapper constructor.
     *
     * @param BeneficiaryMapper $beneficiaryMapper
     */
    public function __construct(BeneficiaryMapper $beneficiaryMapper)
    {
        $this->beneficiaryMapper = $beneficiaryMapper;
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
}
