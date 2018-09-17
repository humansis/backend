<?php


namespace DistributionBundle\Utils;


use BeneficiaryBundle\Utils\Distribution\DefaultRetriever;
use DistributionBundle\Entity\DistributionData;
use DistributionBundle\Entity\SelectionCriteria;
use DistributionBundle\Utils\Retriever\AbstractRetriever;
use Doctrine\ORM\EntityManagerInterface;
use ProjectBundle\Entity\Project;

class CriteriaDistributionService
{

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var ConfigurationLoader $configurationLoader */
    private $configurationLoader;

    /** @var AbstractRetriever $retriever */
    private $retriever;


    /**
     * CriteriaDistributionService constructor.
     * @param EntityManagerInterface $entityManager
     * @param ConfigurationLoader $configurationLoader
     * @param string $classRetrieverString
     * @throws \Exception
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ConfigurationLoader $configurationLoader,
        string $classRetrieverString
    )
    {
        $this->em = $entityManager;
        $this->configurationLoader = $configurationLoader;
        try
        {
            $class = new \ReflectionClass($classRetrieverString);
            $this->retriever = $class->newInstanceArgs([$this->em]);
        }
        catch (\Exception $exception)
        {
            throw new \Exception("Your class Retriever is malformed.");
        }
    }


    /**
     * @param Project $project
     * @param array $filters
     * @param bool $onlyCount
     * @return mixed
     * @throws \Exception
     */
    public function load(Project $project, array $filters, bool $onlyCount = false)
    {
        $countryISO3 = $filters['__country'];
        $distributionType = $filters['distribution_type'];

        return $this->retriever->getReceivers(
            $project,
            $countryISO3,
            $distributionType,
            $filters["criteria"],
            $this->configurationLoader->load($filters),
            $onlyCount
        );
    }

    /**
     * @param DistributionData $distributionData
     * @param SelectionCriteria $selectionCriteria
     * @param bool $flush
     * @return SelectionCriteria
     */
    public function save(DistributionData $distributionData, SelectionCriteria $selectionCriteria, bool $flush)
    {
        $selectionCriteria->setDistributionData($distributionData);
        $this->em->persist($selectionCriteria);
        if ($flush)
            $this->em->flush();
        return $selectionCriteria;
    }

    /**
     * @param array $filters
     * @return array
     */
    public function getAll(array $filters)
    {
        $criteria = $this->configurationLoader->load($filters);
        return $criteria;
    }
}