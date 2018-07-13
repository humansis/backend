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
     * @return mixed
     * @throws \Exception
     */
    public function load(array $filters, bool $onlyCount = false)
    {
        $defaultRetriever = new DefaultRetriever($this->em);
        $countryISO3 = $filters['__country'];
        $distributionType = $filters['distribution_type'];
        $groupGlobal = (array_key_exists('group', $filters) ? $filters['group'] : null);

        return $defaultRetriever->getReceivers($countryISO3, $distributionType, $filters["criteria"], $onlyCount, $groupGlobal);
    }
}