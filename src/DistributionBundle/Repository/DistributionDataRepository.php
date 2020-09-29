<?php

namespace DistributionBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Entity\Location;
use DistributionBundle\Entity\DistributedItem;
use Doctrine\ORM\Query\Expr\Join;
use \DateTime;
use DistributionBundle\Entity\DistributionData;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * DistributionDataRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DistributionDataRepository extends \Doctrine\ORM\EntityRepository
{
    public function getLastId()
    {
        $qb = $this->createQueryBuilder('dd')
                   ->select("MAX(dd.id)");
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getTotalValue(string $country)
    {
        $qb = $this->createQueryBuilder("dd");

        $qb
            ->select("SUM(c.value)")
            ->leftJoin("dd.project", "p")
            ->where("p.iso3 = :country")
                ->setParameter("country", $country)
            ->leftJoin("dd.commodities", "c")
            ->leftJoin("c.modalityType", "mt")
            ->andWhere("mt.name = 'Mobile'");

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getActiveByCountry(string $country)
    {
        $qb = $this->createQueryBuilder("dd")
                    ->leftJoin("dd.project", "p")
                    ->where("p.iso3 = :country")
                    ->setParameter("country", $country)
                    ->andWhere("dd.archived = 0");
        return $qb->getQuery()->getResult();
    }

    public function getCodeOfUpcomingDistribution(string $countryISO)
    {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->addSelect('p')
            ->addSelect('l')
            ->addSelect('adm1')
            ->addSelect('adm2')
            ->addSelect('adm3')
            ->addSelect('adm4')
            ->innerJoin('dd.project', 'p')
            ->innerJoin('dd.location', 'l')
            ->leftJoin('l.adm1', 'adm1')
            ->leftJoin('l.adm2', 'adm2')
            ->leftJoin('l.adm3', 'adm3')
            ->leftJoin('l.adm4', 'adm4')
            ->andWhere('p.iso3 = :country')
                ->setParameter('country', $countryISO)
            ->andWhere('dd.dateDistribution > :now')
                ->setParameter('now', new DateTime());

        return $qb->getQuery()->getResult();
    }

    public function countCompleted(string $countryISO3) {
        $qb = $this->createQueryBuilder('dd');
        $qb->select('COUNT(dd)')
            ->leftJoin("dd.location", "l");
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);
        $qb->andWhere("dd.completed = 1");

        return $qb->getQuery()->getSingleScalarResult();

    }

    /**
     * Returns list of distributions distributed to given beneficiary
     *
     * @param Beneficiary $beneficiary
     * @return DistributionData[]
     */
    public function findDistributedToBeneficiary(Beneficiary $beneficiary)
    {
        $qb = $this->createQueryBuilder('dd')
            ->join('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.beneficiary = :beneficiary')
            ->orderBy('dd.dateDistribution', 'DESC');

        $qb->setParameter('beneficiary', $beneficiary);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns list of distributions distributed to given household.
     *
     * @param Household $household
     *
     * @return DistributedItem[]
     */
    public function findDistributedToHousehold(Household $household): iterable
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(DistributedItem::class, 'di');

        $sql = '
        SELECT '.$rsm->generateSelectClause().' FROM ( 
            SELECT dd.*, db.beneficiary_id FROM distribution_data dd
            JOIN distribution_beneficiary db ON dd.id=db.distribution_data_id
            JOIN beneficiary b ON b.id=db.beneficiary_id
            WHERE b.household_id = :household
        ) AS di
        ORDER BY di.date_distribution ASC
        ';

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('household', $household)
            ->getResult();
    }
}
