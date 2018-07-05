<?php


namespace DistributionBundle\Utils;


use DistributionBundle\Utils\Retriever\DefaultRetriever;
use Doctrine\ORM\EntityManagerInterface;

class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;


    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }


    /**
     * @param array $filters
     * @throws \Exception
     */
    public function load(array $filters)
    {
        $defaultRetriever = new DefaultRetriever($this->em);
        $countryISO3 = $filters['__country'];
        $distributionType = $filters['distribution_type'];

        return $defaultRetriever->getReceivers($countryISO3, $distributionType, $filters["criteria"]);
    }
}