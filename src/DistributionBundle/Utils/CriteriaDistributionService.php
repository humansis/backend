<?php


namespace DistributionBundle\Utils;


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
        $kindBeneficiaryGlobal = (array_key_exists('kind_beneficiary', $filters) ? $filters['kind_beneficiary'] : null);

        return $defaultRetriever->getReceivers($countryISO3, $distributionType, $filters["criteria"], $onlyCount, $kindBeneficiaryGlobal);
    }

    public function save(SelectionCriteria $selectionCriteria, bool $flush)
    {
        $this->em->persist($selectionCriteria);
        if ($flush)
            $this->em->flush();
        return $selectionCriteria;
    }

    public function getAll($countryISO3)
    {
        $criteria = $this->configurationLoader->load();

        return $criteria;
    }
}