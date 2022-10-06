<?php

namespace Repository;

use Doctrine\ORM\QueryBuilder;
use Entity\Project;

interface InterfaceCriteriaRepository
{
    public function findByCriteria(
        string $countryISO3,
        array $criteria,
        Project $project,
        array $configurationCriteria = [],
        bool $onlyCount = false,
        string $groupGlobal = null
    );

    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, array $filters);

    public function configurationQueryBuilder(bool $onlyCount, string $countryISO3, Project $project);
}
