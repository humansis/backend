<?php


namespace DistributionBundle\Repository;


use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Repository\BeneficiaryRepository;
use BeneficiaryBundle\Repository\HouseholdRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use ProjectBundle\Entity\Project;

abstract class AbstractCriteriaRepository extends EntityRepository implements InterfaceCriteriaRepository
{

    /**
     * @param Project $project
     * @param array $countryISO3
     * @param array $criteria
     * @param array $configurationCriteria
     * @param bool $onlyCount
     * @param string|null $groupGlobal
     * @return mixed
     * @throws \Exception
     * @throws \ReflectionException
     */
    public function findByCriteria(
        Project $project = null,
        $countryISO3,
        array $filters,
        array $configurationCriteria = [],
        bool $onlyCount = false,
        string $groupGlobal = null
    )
    {
        $criteria = $filters['criteria'];
        $distributionType = $filters['distribution_type'];

        $qb = $this->configurationQueryBuilder($onlyCount, $countryISO3, $project);

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


                if($distributionType == "household"){
                    $className = new ClassMetadata("BeneficiaryBundle\Entity\Household");
                    $callFunc = new HouseholdRepository($this->getEntityManager(), $className);
                }
                else{
                    $className = new ClassMetadata("BeneficiaryBundle\Entity\Beneficiary");
                    $callFunc = new BeneficiaryRepository($this->getEntityManager(), $className);
                }

                if (!is_callable([$callFunc, 'where' . $class->getShortName()],null, $method))
                    throw new \Exception("You must implement a method called 'where{$class->getShortName()}'.'");
                call_user_func_array([$callFunc, $method], [&$qb, $i, $countryISO3, $criterion]);
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
     * @param Project $project
     * @throws \Exception
     */
    public function configurationQueryBuilder($onlyCount, $countryISO3, Project $project)
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

    /**
     * Set the country iso3 in the query on Household (with alias 'hh{id}'
     *
     * @param QueryBuilder $qb
     * @param $countryISO3
     * @param string $i
     */
    protected function setCountry(QueryBuilder &$qb, $countryISO3, $i = '')
    {
        $qb->leftJoin("hh$i.location", "l$i")

            ->leftJoin("l$i.adm1", "adm1$i")
            ->andWhere("adm1$i.countryISO3 = :iso3 AND hh$i.archived = 0")

            ->leftJoin("l$i.adm4", "adm4$i")
            ->leftJoin("adm4$i.adm3", "adm3b$i")
            ->leftJoin("adm3b$i.adm2", "adm2b$i")
            ->leftJoin("adm2b$i.adm1", "adm1b$i")
            ->orWhere("adm1b$i.countryISO3 = :iso3 AND hh$i.archived = 0")

            ->leftJoin("l$i.adm3", "adm3$i")
            ->leftJoin("adm3$i.adm2", "adm2c$i")
            ->leftJoin("adm2c$i.adm1", "adm1c$i")
            ->orWhere("adm1c$i.countryISO3 = :iso3 AND hh$i.archived = 0")

            ->leftJoin("l$i.adm2", "adm2$i")
            ->leftJoin("adm2$i.adm1", "adm1d$i")
            ->orWhere("adm1d$i.countryISO3 = :iso3 AND hh$i.archived = 0")

            ->setParameter("iso3", $countryISO3);
    }
}