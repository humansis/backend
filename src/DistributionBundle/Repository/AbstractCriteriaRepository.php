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
        $qb = $this->configurationQueryBuilder($onlyCount, $countryISO3, $groupGlobal);

        $i = 1;
        foreach ($criteria as $criterion)
        {
            $configType = null;
            if ("default" === strtolower($criterion["table_string"]))
            {
                $configType = strtolower($criterion["table_string"]);
                $this->whereDefault($qb, $i, $countryISO3, $criterion['field_string'], $criterion['value_string'], $criterion['condition_string'], $criterion['kind_beneficiary']);
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

                call_user_func_array([$this, $method], [&$qb, $i, $countryISO3, $criterion['id_field'], $criterion['value_string'], $criterion['condition_string']]);
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
    public function configurationQueryBuilder($onlyCount, $countryISO3, $groupGlobal)
    {
        throw new \Exception("configurationQueryBuilder must de implemented.");
    }

    /**
     * @param QueryBuilder $qb
     * @param $i
     * @param $countryISO3
     * @param $field
     * @param $value
     * @param $operator
     * @param bool|null $status
     * @throws \Exception
     */
    public function whereDefault(QueryBuilder &$qb, $i, $countryISO3, $field, $value, $operator, bool $status = null)
    {
        throw new \Exception("whereDefault must de implemented.");
    }
}