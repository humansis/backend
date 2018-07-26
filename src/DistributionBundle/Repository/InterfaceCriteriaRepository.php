<?php


namespace DistributionBundle\Repository;


use Doctrine\ORM\QueryBuilder;

interface InterfaceCriteriaRepository
{
    public function findByCriteria(
        $countryISO3,
        array $criteria,
        array $configurationCriteria = [],
        bool $onlyCount = false,
        string $groupGlobal = null
    );

//    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, $field, $value, $operator, bool $status = null);
    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, array $filters);

    public function configurationQueryBuilder($onlyCount, $countryISO3, $groupGlobal);

}