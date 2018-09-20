<?php


namespace DistributionBundle\Utils\Retriever;


use ProjectBundle\Entity\Project;

abstract class AbstractRetriever
{
    /**
     * @param Project $project
     * @param string $countryISO3
     * @param string $distributionType
     * @param array $filters
     * @param array $configurationCriteria
     * @param bool $onlyCount
     * @return array
     */
    public function getReceivers(
        Project $project,
        string $countryISO3,
        string $distributionType,
        array $filters,
        array $configurationCriteria,
        bool $onlyCount = false
    )
    {
        $this->preFinder($distributionType, $filters['criteria']);

        $receivers = $this->guessRepository($distributionType)
            ->findByCriteria($project, $countryISO3, $filters, $configurationCriteria, $onlyCount);

        // If we only want the number of beneficiaries, return only the number
        if ($onlyCount)
        {
            $receivers = ["number" => intval(current($receivers)[1])];
        }

        return $receivers;
    }

    /**
     * This function is called before the SQL treatment. You can reformat your criteria before send it to the repositories
     *
     * @param string $distributionType
     * @param array $criteria
     */
    protected function preFinder(string $distributionType, array &$criteria)
    {
    }

    /**
     * Define which repository will be used to found the beneficiary
     *
     * @param string $distributionType
     * @return null
     */
    protected function guessRepository(string $distributionType)
    {
        return null;
    }
}