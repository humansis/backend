<?php


namespace NewApiBundle\Mapper;


use NewApiBundle\Entity\Assistance;

class AssistanceMapper extends \BeneficiaryBundle\Mapper\AssistanceMapper
{
    /** @var CommodityMapper */
    private $commodityMapper;

    public function __construct(BeneficiaryMapper $beneficiaryMapper, CommodityMapper $commodityMapper)
    {
        parent::__construct($beneficiaryMapper);

        $this->commodityMapper = $commodityMapper;
    }


    public function toFullArray(?Assistance $assistance): ?array
    {
        if (!$assistance) {
            return null;
        }

        return [
            'id' => $assistance->getId(),
            'name' => $assistance->getName(),
            'date' => $assistance->getDateDistribution()->getTimestamp(),
            'target' => $assistance->getTargetTypeString(),
            'type' => $assistance->getAssistanceType(),
            'province' => $assistance->getLocation()->getAdm1Name(),
            'district' => $assistance->getLocation()->getAdm2Name(),
            'commune' => $assistance->getLocation()->getAdm3Name(),
            'village' => $assistance->getLocation()->getAdm4Name(),
            'modality-icons' => $this->commodityMapper->toModalityIcons($assistance->getCommodities()),
        ];
    }


    /**
     * @param iterable $assistances
     *
     * @return iterable
     */
    public function toFullArrays(iterable $assistances): iterable
    {
        foreach ($assistances as $assistance) {
            yield $this->toFullArray($assistance);
        }
    }
}