<?php

namespace CommonBundle\Repository;

use CommonBundle\Entity\Location;
use Doctrine\ORM\QueryBuilder;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm1;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\LocationFilterInputType;

/**
 * LocationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param $country
     * @return Location[]
     */
    public function getByCountry($country)
    {
        $qb = $this->createQueryBuilder('l');
        $qb->leftJoin('l.adm1', 'amd1');
        $this->whereCountry($qb, $country);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $countryIso3
     * @param array  $adms full path of adms from Adm1 to whatever level (for example [adm1, adm2, adm3])
     *
     * @return Location|null
     */
    public function getByNormalizedNames(string $countryIso3, array $adms): ?Location
    {
        $level = count($adms);

        $lowestLevelLocation = $this->createQueryBuilder('l')
            ->where('l.countryISO3 = :country')
            ->andWhere('l.enumNormalizedName = :normalizedName')
            ->andWhere('l.lvl = :level')
            ->setParameter('country', $countryIso3)
            ->setParameter('normalizedName', end($adms))
            ->setParameter('level', $level)
            ->getQuery()
            ->getResult();

        /** @var Location $location */
        foreach ($lowestLevelLocation as $key => $location) {
            $currentLevel = $level - 1;

            $currentLevelLocation = $location;

            while ($currentLevel > 0) {
                $parent = $currentLevelLocation->getParentLocation();

                if ($parent->getEnumNormalizedName() !== $adms[$currentLevel - 1]) {
                    unset($lowestLevelLocation[$key]);
                }

                $currentLevelLocation = $parent;
                $currentLevel--;
            }
        }

        if (empty($lowestLevelLocation)) {
            return null;
        } else {
            return current($lowestLevelLocation);
        }
    }

    public function getByNames(string $countryIso3, ?string $adm1, ?string $adm2, ?string $adm3, ?string $adm4): ?Location
    {
        $qb = $this->createQueryBuilder('l');
        $qb->setMaxResults(1);

        if (null !== $adm4) {
            $qb->join('l.adm4', 'adm4')
                ->andWhere('adm4.name LIKE :adm4')
                ->setParameter('adm4', $adm4);

            $qb->join('adm4.adm3', 'adm3')
                ->andWhere('adm3.name LIKE :adm3')
                ->setParameter('adm3', $adm3);

            $qb->join('adm3.adm2', 'adm2')
                ->andWhere('adm2.name LIKE :adm2')
                ->setParameter('adm2', $adm2);

            $qb->join('adm2.adm1', 'adm1')
                ->andWhere('adm1.name LIKE :adm1')
                ->andWhere('adm1.countryISO3 = :country')
                ->setParameter('adm1', $adm1)
                ->setParameter('country', $countryIso3)
            ;

            return $qb->getQuery()->getOneOrNullResult();
        }

        if (null !== $adm3) {
            $qb->join('l.adm3', 'adm3')
                ->andWhere('adm3.name LIKE :adm3')
                ->setParameter('adm3', $adm3);

            $qb->join('adm3.adm2', 'adm2')
                ->andWhere('adm2.name LIKE :adm2')
                ->setParameter('adm2', $adm2);

            $qb->join('adm2.adm1', 'adm1')
                ->andWhere('adm1.name LIKE :adm1')
                ->andWhere('adm1.countryISO3 = :country')
                ->setParameter('adm1', $adm1)
                ->setParameter('country', $countryIso3)
            ;

            return $qb->getQuery()->getOneOrNullResult();
        }

        if (null !== $adm2) {
            $qb->join('l.adm2', 'adm2')
                ->andWhere('adm2.name LIKE :adm2')
                ->setParameter('adm2', $adm2);

            $qb->join('adm2.adm1', 'adm1')
                ->andWhere('adm1.name LIKE :adm1')
                ->andWhere('adm1.countryISO3 = :country')
                ->setParameter('adm1', $adm1)
                ->setParameter('country', $countryIso3)
            ;

            return $qb->getQuery()->getOneOrNullResult();
        }

        if (null !== $adm1) {
            $qb->join('l.adm1', 'adm1')
                ->andWhere('adm1.name LIKE :adm1')
                ->andWhere('adm1.countryISO3 = :country')
                ->setParameter('adm1', $adm1)
                ->setParameter('country', $countryIso3)
            ;

            return $qb->getQuery()->getOneOrNullResult();
        }

        return null;
    }

    /**
     * Create sub request to get the adm1 of a location
     *
     * @param QueryBuilder $qb
     */
    public function getAdm1(QueryBuilder &$qb)
    {
        $qb->leftJoin("l.adm4", "adm4")
            ->leftJoin("l.adm3", "locAdm3")
            ->leftJoin("l.adm2", "locAdm2")
            ->leftJoin("l.adm1", "locAdm1")
            ->leftJoin(Adm3::class, "adm3", Join::WITH, "adm3.id = COALESCE(IDENTITY(adm4.adm3, 'id'), locAdm3.id)")
            ->leftJoin(Adm2::class, "adm2", Join::WITH, "adm2.id = COALESCE(IDENTITY(adm3.adm2, 'id'), locAdm2.id)")
            ->leftJoin(Adm1::class, "adm1", Join::WITH, "adm1.id = COALESCE(IDENTITY(adm2.adm1, 'id'), locAdm1.id)");
    }

    /**
     * Create sub request to get the adm2 of a location
     *
     * @param QueryBuilder $qb
     */
    public function getAdm2(QueryBuilder &$qb)
    {
        $qb->leftJoin("l.adm4", "adm4")
            ->leftJoin("l.adm3", "locAdm3")
            ->leftJoin("l.adm2", "locAdm2")
            ->leftJoin(Adm3::class, "adm3", Join::WITH, "adm3.id = COALESCE(IDENTITY(adm4.adm3, 'id'), locAdm3.id)")
            ->leftJoin(Adm2::class, "adm2", Join::WITH, "adm2.id = COALESCE(IDENTITY(adm3.adm2, 'id'), locAdm2.id)");
    }

     /**
     * Create sub request to get the adm3 of a location
     *
     * @param QueryBuilder $qb
     */
    public function getAdm3(QueryBuilder &$qb)
    {
        $qb->leftJoin("l.adm4", "adm4")
            ->leftJoin("l.adm3", "locAdm3")
            ->leftJoin(Adm3::class, "adm3", Join::WITH, "adm3.id = COALESCE(IDENTITY(adm4.adm3, 'id'), locAdm3.id)");
    }

    /**
     * Create sub request to get items in country.
     * The location must be in the country ($countryISO3).
     *
     * @param QueryBuilder $qb
     * @param $countryISO3
     */
    public function whereCountry(QueryBuilder &$qb, $countryISO3)
    {
        $this->getAdm1($qb);
        $qb->andWhere("adm1.countryISO3 = :iso3")
            ->setParameter("iso3", $countryISO3);
    }

    public function getCountry(QueryBuilder &$qb)
    {
        $this->getAdm1($qb);
        $qb->select("adm1.countryISO3 as country");
    }

    public static function joinPathToRoot(QueryBuilder $qb, string $locationCurrentAlias, string $pathAlias) {
        $qb->leftJoin(
            Location::class,
            $pathAlias,
            Join::WITH,
            "($pathAlias.rgt >= $locationCurrentAlias.rgt 
                AND $pathAlias.lft <= $locationCurrentAlias.lft 
                AND $pathAlias.lvl <= $locationCurrentAlias.lvl)");
    }


    /**
     * @param LocationFilterInputType $filter
     *
     * @return Paginator
     */
    public function findByParams(LocationFilterInputType $filter): Paginator
    {
        $qbr = $this->createQueryBuilder('l');

        if ($filter->hasIds()) {
            $qbr->andWhere('l.id IN (:ids)')
                ->setParameter('ids', $filter->getIds());
        }

        return new Paginator($qbr);
    }

    /**
     * @param mixed $locationId
     *
     * @return int[]
     */
    public function findDescendantLocations($locationId): iterable
    {
        return $this->_em->getConnection()
            ->executeQuery('
                WITH RECURSIVE loc (loc_id, loc_parent_id) AS (
                    SELECT location_id, parent_location_id FROM view_location_recursive WHERE location_id=?         
                    UNION ALL 
                    SELECT location_id, parent_location_id FROM view_location_recursive JOIN loc ON parent_location_id=loc_id
                )
                SELECT DISTINCT loc_id FROM loc', [$locationId])
            ->fetchFirstColumn();
    }
}
