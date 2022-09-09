<?php

namespace CommonBundle\Repository;

use CommonBundle\Entity\Location;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\LocationFilterInputType;

/**
 * LocationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 *
 * @method Location|null find($id, $lockMode = null, $lockVersion = null)
 */
class LocationRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param $country
     *
     * @return Location[]
     */
    public function getByCountry($country)
    {
        $qb = $this->createQueryBuilder('l');
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
            ->where('l.countryIso3 = :country')
            ->andWhere('l.enumNormalizedName = :normalizedName')
            ->andWhere('l.lvl = :level')
            ->setParameter('country', $countryIso3)
            ->setParameter('normalizedName', end($adms))
            ->setParameter('level', $level)
            ->getQuery()
            ->getResult();

        /** @var Location $location */
        foreach ($lowestLevelLocation as $key => $location) {
            if ($this->isLocationEqualAdmPath($location, $adms)) {
                return $location;
            }
        }

        return null;
    }

    /**
     * It will iterate through location path and check if all parts of location are equal to ADMs array
     *
     * @param Location $location
     * @param array    $adms
     *
     * @return bool
     */
    private function isLocationEqualAdmPath(Location $location, array $adms): bool
    {
        $parentLevel = count($adms) - 2;
        $currentLevelLocation = $location;
        while (($parent = $currentLevelLocation->getParentLocation()) && $parentLevel > 0) {
            if ($parent->getEnumNormalizedName() !== $adms[$parentLevel]) {
                return false;
            }
            $currentLevelLocation = $parent;
            $parentLevel--;
        }

        return true;
    }

    /**
     * Create sub request to get items in country.
     * The location must be in the country ($countryISO3).
     *
     * @param QueryBuilder $qb
     * @param              $countryISO3
     */
    public function whereCountry(QueryBuilder &$qb, $countryISO3)
    {
        $qb->andWhere("l.countryIso3 = :iso3")
            ->setParameter("iso3", $countryISO3);
    }

    public function getCountry(QueryBuilder &$qb)
    {
        $qb->select("l.countryISO3 as country");
    }

    public static function joinPathToRoot(QueryBuilder $qb, string $locationCurrentAlias, string $pathAlias)
    {
        $qb->leftJoin(
            Location::class,
            $pathAlias,
            Join::WITH,
            "($pathAlias.rgt >= $locationCurrentAlias.rgt 
                AND $pathAlias.lft <= $locationCurrentAlias.lft 
                AND $pathAlias.lvl <= $locationCurrentAlias.lvl)");
    }

    /**
     * @param Location $location
     *
     * @return Location[]
     */
    public function getChildrenLocations(Location $location): array
    {
        return $this->getChildrenLocationsQueryBuilder($location)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Location $location
     *
     * @return QueryBuilder
     */
    public function getChildrenLocationsQueryBuilder(Location $location): QueryBuilder
    {
        $qb = $this->createQueryBuilder('l');

        return $qb->andWhere(
            $qb->expr()->lte('l.rgt', ':currentRgt'),
            $qb->expr()->gte('l.lft', ':currentLft'),
            $qb->expr()->gte('l.lvl', ':currentLvl')
        )
            ->setParameters([
                'currentRgt' => $location->getRgt(),
                'currentLft' => $location->getLft(),
                'currentLvl' => $location->getLvl(),
            ]);
    }

    /**
     * return query for children locations (to be used in subquery)
     *
     * @param Location $ancestor
     * @param string $childAlias
     * @param bool $withParent - include parent in the query
     * @return QueryBuilder
     */
    public function addChildrenLocationsQueryBuilder(
        Location $ancestor,
        string $childAlias = 'subqChildLoc',
        bool $withParent = false
    ): QueryBuilder
    {
        $qb = $this->createQueryBuilder($childAlias);
        
        return $this->inChildrenLocationsQueryBuilder(
            $qb,
            $ancestor,
            $childAlias,
            $withParent,
        );
        
    }

    /**
     * add join for children locations to query in param
     * 
     * @param QueryBuilder $qb
     * @param Location $ancestor
     * @param string $joinAlias
     * @param string $childAlias
     * @param bool $withParent
     * @return QueryBuilder
     */
    public function joinChildrenLocationsQueryBuilder(
        QueryBuilder $qb,
        Location $ancestor,
        string $joinAlias,
        string $childAlias = 'subqChildLoc',
        bool $withParent = false
    ): QueryBuilder
    {
        $qb->join($joinAlias . '.location', $childAlias);

        return $this->inChildrenLocationsQueryBuilder(
            $qb,
            $ancestor,
            $childAlias,
            $withParent,
        );
    }

    /**
     * @param int $level
     * @param string $childAlias
     * @param string $parentAlias
     * @return QueryBuilder
     */
    public function addParentLocationFulltextSubQueryBuilder(
        int $level,
        string $childAlias,
        string $parentAlias = 'subqParentLoc'
    ): QueryBuilder
    {
        $qb = $this->createQueryBuilder($parentAlias);
        return $qb
            ->andWhere($qb->expr()->between(
                $childAlias .'.lft', 
                $parentAlias . '.lft',
                $parentAlias . '.rgt'))
            ->andWhere($parentAlias . '.lvl = :' . $parentAlias . 'Level')
            ->andWhere($parentAlias . '.countryIso3 = :iso3')
            ->andWhere($parentAlias . '.name like :fulltext')
            ->setParameter($parentAlias . 'Level', $level);
    }

    /**
     * @param LocationFilterInputType $filter
     * @param string|null             $iso3
     *
     * @return Paginator
     */
    public function findByParams(LocationFilterInputType $filter, ?string $iso3 = null): Paginator
    {
        $qbr = $this->createQueryBuilder('l');

        if ($iso3) {
            $qbr->andWhere('l.countryIso3 = :iso3')
                ->setParameter('iso3', $iso3);
        }
        if ($filter->hasIds()) {
            $qbr->andWhere('l.id IN (:ids)')
                ->setParameter('ids', $filter->getIds());
        }
        if ($filter->hasFulltext()) {
            $orX = $qbr->expr()->orX();
            $orX
                ->add($qbr->expr()->eq('l.id', ':id'))
                ->add($qbr->expr()->like('l.name', ':fulltext'))
                ->add($qbr->expr()->like('l.code', ':fulltext'));
            $qbr->andWhere($orX);
            $qbr->setParameter('id', $filter->getFulltext());
            $qbr->setParameter('fulltext', '%'.$filter->getFulltext().'%');
        }
        if ($filter->hasLevel()) {
            $qbr->andWhere('l.lvl = :level')
                ->setParameter('level', $filter->getLevel());
        }
        if ($filter->hasParent()) {
            $qbr->andWhere('l.parentLocation = :parent')
                ->setParameter('parent', $filter->getParent());
        }

        $qbr->orderBy('l.name', 'ASC');

        return new Paginator($qbr);
    }

    /**
     * @param string      $code
     * @param string|null $iso3
     * @param array|null  $context
     *
     * @return Location[]
     */
    public function findLocationsByCode(string $code, ?string $iso3 = null): array
    {
        $qb = $this->createQueryBuilder('l');
        $qb->andWhere('l.code = :code')
            ->setParameter('code', $code);
        if ($iso3) {
            $qb->andWhere('l.countryIso3 = :iso3');
            $qb->setParameter('iso3', $iso3);
        }

        return $qb->getQuery()->getResult();
    }
    
    private function inChildrenLocationsQueryBuilder(
        QueryBuilder $qb,
        Location $ancestor,
        string $childAlias = 'subqChildLoc',
        bool $withParent = false
    ): QueryBuilder
    {
        if ($withParent) {
            //include parent in the query
            $qb
                ->andWhere(
                    $qb->expr()->lte($childAlias.'.rgt', ':parentRgt'),
                    $qb->expr()->gte($childAlias.'.lft', ':parentLft'),
                    $qb->expr()->gte($childAlias.'.lvl', ':parentLvl')
                );
        }
        else {
            //get only children
            $qb
                ->andWhere(
                    $qb->expr()->lt($childAlias.'.rgt', ':parentRgt'),
                    $qb->expr()->gt($childAlias.'.lft', ':parentLft'),
                    $qb->expr()->gt($childAlias.'.lvl', ':parentLvl')
                );
        }

        return $qb->andWhere($childAlias . '.countryIso3 = :iso3')
            ->setParameter('parentRgt', $ancestor->getRgt())
            ->setParameter('parentLft', $ancestor->getLft())
            ->setParameter('parentLvl', $ancestor->getLvl());
    }
}
