<?php

namespace DistributionBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Entity\DistributedItem;
use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

class DistributedItemRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Returns list of distributions distributed to given beneficiary
     *
     * @param Beneficiary $beneficiary
     * @return Assistance[]
     */
    public function findDistributedToBeneficiary(Beneficiary $beneficiary)
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(DistributedItem::class, 'di');

        $sql = '
        SELECT '.$rsm->generateSelectClause().' FROM ( 
            SELECT
                ass.id,
                ass.id AS assistance_id,
                ass.name,
                ass.target_type,
                db.beneficiary_id,
                CASE
                    WHEN sd.id IS NOT NULL THEN DATE_FORMAT(sd.used_at, "%Y-%m-%d")
                    WHEN gri.id IS NOT NULL THEN DATE_FORMAT(gri.distributedAt, "%Y-%m-%d")
                    WHEN t.id IS NOT NULL THEN DATE_FORMAT(t.date_sent, "%Y-%m-%d")
                END AS date_distribution
            FROM assistance ass
            JOIN distribution_beneficiary db ON ass.id=db.assistance_id
            -- smartcards
            LEFT JOIN assistance_beneficiary_commodity abc ON abc.assistance_beneficiary_id=db.id
            LEFT JOIN smartcard_deposit sd ON sd.assistance_beneficiary_commodity_id=abc.id
            -- mobile money
            LEFT JOIN transaction t ON t.distribution_beneficiary_id=db.id
            -- general reliefs
            LEFT JOIN general_relief_item gri ON gri.distribution_beneficiary_id=db.id
            WHERE db.beneficiary_id = :beneficiary AND (sd.id IS NOT NULL OR gri.id IS NOT NULL OR t.id IS NOT NULL)
        ) AS di
        ORDER BY di.date_distribution ASC
        ';

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('beneficiary', $beneficiary)
            ->getResult();
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
            SELECT
                ass.id,
                ass.id AS assistance_id,
                ass.name,
                ass.target_type,
                db.beneficiary_id,
                CASE
                    WHEN sd.id IS NOT NULL THEN DATE_FORMAT(sd.used_at, "%Y-%m-%d")
                    WHEN gri.id IS NOT NULL THEN DATE_FORMAT(gri.distributedAt, "%Y-%m-%d")
                    WHEN t.id IS NOT NULL THEN DATE_FORMAT(t.date_sent, "%Y-%m-%d")
                END AS date_distribution
            FROM assistance ass
            JOIN distribution_beneficiary db ON ass.id=db.assistance_id
            JOIN beneficiary b ON b.id=db.beneficiary_id
            -- smartcards
            LEFT JOIN assistance_beneficiary_commodity abc ON abc.assistance_beneficiary_id=db.id
            LEFT JOIN smartcard_deposit sd ON sd.assistance_beneficiary_commodity_id=abc.id
            -- mobile money
            LEFT JOIN transaction t ON t.distribution_beneficiary_id=db.id
            -- general reliefs
            LEFT JOIN general_relief_item gri ON gri.distribution_beneficiary_id=db.id
            WHERE b.household_id = :household AND (sd.id IS NOT NULL OR gri.id IS NOT NULL OR t.id IS NOT NULL)
        ) AS di
        ORDER BY di.date_distribution ASC
        ';

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('household', $household)
            ->getResult();
    }
}
