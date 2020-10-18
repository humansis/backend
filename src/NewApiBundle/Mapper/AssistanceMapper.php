<?php


namespace NewApiBundle\Mapper;


use NewApiBundle\Entity\Assistance;

class AssistanceMapper extends \BeneficiaryBundle\Mapper\AssistanceMapper
{
    public function __construct(BeneficiaryMapper $beneficiaryMapper)
    {
        parent::__construct($beneficiaryMapper);
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
            'target' => $assistance->getTargetTypeString(), //TODO is it correct? (to string mapping - no individual)
            'type' => $assistance->getAssistanceType(),
            'province' => $assistance->getLocation()->getAdm1Name(),
            'district' => $assistance->getLocation()->getAdm2Name(),
            'commune' => $assistance->getLocation()->getAdm3Name(),
            'village' => $assistance->getLocation()->getAdm4Name(),
            'commodity' => '' //TODO asiistance:commodity is 1:n - which one will be used?
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