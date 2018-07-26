<?php


namespace DistributionBundle\Utils;


use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\Retriever\DefaultRetriever;
use Doctrine\ORM\EntityManagerInterface;

class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;


    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader
    )
    {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
    }


    /**
     * @param array $filters
     * @param bool $onlyCount
     * @return mixed
     * @throws \Exception
     */
    public function load(array $filters, bool $onlyCount = false)
    {
        $defaultRetriever = new DefaultRetriever($this->em);
        $countryISO3 = $filters['__country'];
        $distributionType = $filters['distribution_type'];

        return $defaultRetriever->getReceivers(
            $countryISO3,
            $distributionType,
            $filters["criteria"],
            $this->configurationLoader->load($filters),
            $onlyCount
        );
    }

    public function save(DistributionData $distributionData, SelectionCriteria $selectionCriteria, bool $flush)
    {
        $selectionCriteria->setDistributionData($distributionData);
        $this->em->persist($selectionCriteria);
        if ($flush)
            $this->em->flush();
        return $selectionCriteria;
    }

    public function getAll(array $filters)
    {
        $criteria = $this->configurationLoader->load($filters);
        return $criteria;
    }
}