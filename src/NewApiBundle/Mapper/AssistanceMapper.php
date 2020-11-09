<?php


namespace NewApiBundle\Mapper;


use DistributionBundle\Entity\Assistance;

//TODO This is a draft
class AssistanceMapper
{

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
            'commodityIds' => [0, 1] //TODO implement
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