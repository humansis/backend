<?php


namespace DistributionBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractCriteriaRepository extends EntityRepository implements InterfaceCriteriaRepository
{

    /**
     * @param $countryISO3
     * @param array $criteria
     * @param array $configurationCriteria
     * @param bool $onlyCount
     * @param string|null $groupGlobal
     * @return mixed
     * @throws \Exception
     */
    public function findByCriteria(
        $countryISO3,
        array $criteria,
        array $configurationCriteria = [],
        bool $onlyCount = false,
        string $groupGlobal = null
    )
    {
        $qb = $this->configurationQueryBuilder($onlyCount, $countryISO3);

        $i = 1;
        foreach ($criteria as $criterion)
        {
            $configType = null;
            if (!array_key_exists("table_string", $criterion) || "default" === strtolower($criterion["table_string"]))
            {
                $configType = strtolower($criterion["table_string"]);
                $this->whereDefault($qb, $i, $countryISO3, $criterion);
            }
            else
            {
                foreach ($configurationCriteria as $configurationCriterion)
                {
                    if (is_array($configurationCriterion))
                        continue;

                    if ($configurationCriterion->getTableString() == $criterion["table_string"]
                        && $configurationCriterion->getId() == $criterion["id_field"])
                    {
                        $configType = get_class($configurationCriterion);
                        break;
                    }
                }

                $class = (new \ReflectionClass($configType));
                $method = null;
                if (!is_callable([$this, 'where' . $class->getShortName()],null, $method))
                    throw new \Exception("You must implement a method called 'where{$class->getShortName()}'.'");
                call_user_func_array([$this, $method], [&$qb, $i, $countryISO3, $criterion]);
            }
            if (null === $configType)
                throw new \Exception("The field '{$criterion['field_string']}' is not implement yet");
            $i++;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $onlyCount
     * @param $countryISO3
     * @param $groupGlobal
     * @throws \Exception
     */
    public function configurationQueryBuilder($onlyCount, $countryISO3)
    {
        throw new \Exception("configurationQueryBuilder must de implemented.");
    }

    /**
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param array $filters
     * @throws \Exception
     */
    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, array $filters)
    {
        throw new \Exception("whereDefault must de implemented.");
    }
}