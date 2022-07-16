<?php


namespace DistributionBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use NewApiBundle\Entity\Project;

interface InterfaceCriteriaRepository
{
    public function findByCriteria(
        Project $project,
        $countryISO3,
        array $criteria,
        array $configurationCriteria = [],
        bool $onlyCount = false,
        string $groupGlobal = null
    );

    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, array $filters);

    public function configurationQueryBuilder($onlyCount, $countryISO3, Project $project);
}
