<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\InputType\AssistanceStatisticsFilterInputType;

class AssistanceStatisticsRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param Assistance $assistance
     *
     * @return AssistanceStatistics
     */
    public function findByAssistance(Assistance $assistance): AssistanceStatistics
    {
        $sql = '
        SELECT
            :assistance AS id,
            COUNT(beneficiary) AS numberOfBeneficiaries,
            CAST(SUM(totalItem) AS decimal(10, 2)) AS summaryOfTotalItems,
            CAST(SUM(distributedItem) AS decimal(10, 2)) AS summaryOfDistributedItems,
            CAST(SUM(usedItem) AS decimal(10, 2)) AS summaryOfUsedItems
        FROM (
             SELECT
                 CASE WHEN db.removed=0 THEN 1 END AS beneficiary,

                 CASE 
                     WHEN db.removed=0 AND sd.id IS NOT NULL THEN sd.value
                     WHEN db.removed=0 AND  gri.id IS NOT NULL THEN c.value
                     WHEN db.removed=0 AND  t.id IS NOT NULL THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 END AS totalItem,

                 CASE 
                     WHEN db.removed=0 AND  sd.id IS NOT NULL THEN sd.value
                     WHEN db.removed=0 AND  gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                     WHEN db.removed=0 AND  t.id IS NOT NULL AND t.amount_sent IS NOT NULL THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 END AS distributedItem,

                 CASE 
                     WHEN db.removed=0 AND  sd.id IS NOT NULL THEN sd.value
                     WHEN db.removed=0 AND  gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                     WHEN db.removed=0 AND  t.id IS NOT NULL AND t.money_received = 1 THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 END AS usedItem

            FROM distribution_beneficiary db
            JOIN commodity c ON db.assistance_id=c.assistance_id
            -- smartcards
            LEFT JOIN smartcard_deposit sd ON sd.distribution_beneficiary_id=db.id
            -- mobile money
            LEFT JOIN transaction t ON t.distribution_beneficiary_id=db.id
            -- general reliefs
            LEFT JOIN general_relief_item gri ON gri.distribution_beneficiary_id=db.id
            WHERE db.assistance_id=:assistance AND (sd.id IS NOT NULL OR gri.id IS NOT NULL OR t.id IS NOT NULL)
        ) AS counts
        ';

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(AssistanceStatistics::class, 'as');

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('assistance', $assistance->getId())
            ->getSingleResult();
    }

    /**
     * @param string                              $countryIso3
     * @param AssistanceStatisticsFilterInputType $filter
     *
     * @return AssistanceStatistics[]|Paginator
     */
    public function findByParams(string $countryIso3, AssistanceStatisticsFilterInputType $filter): iterable
    {
        $assistanceBinds = [];
        foreach ($filter->getIds() as $key => $id) {
            $assistanceBinds[] = ':assistance'.$key;
        }

        $sql = '
        SELECT
            assistance_id AS id,
            COUNT(beneficiary) AS numberOfBeneficiaries,
            CAST(SUM(totalItem) AS decimal(10, 2)) AS summaryOfTotalItems,
            CAST(SUM(distributedItem) AS decimal(10, 2)) AS summaryOfDistributedItems,
            CAST(SUM(usedItem) AS decimal(10, 2)) AS summaryOfUsedItems
        FROM (
             SELECT
                 db.assistance_id,

                 CASE WHEN db.removed=0 THEN 1 END AS beneficiary,

                 CASE 
                     WHEN db.removed=0 AND sd.id IS NOT NULL THEN sd.value
                     WHEN db.removed=0 AND  gri.id IS NOT NULL THEN c.value
                     WHEN db.removed=0 AND  t.id IS NOT NULL THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 END AS totalItem,

                 CASE 
                     WHEN db.removed=0 AND  sd.id IS NOT NULL THEN sd.value
                     WHEN db.removed=0 AND  gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                     WHEN db.removed=0 AND  t.id IS NOT NULL AND t.amount_sent IS NOT NULL THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 END AS distributedItem,

                 CASE 
                     WHEN db.removed=0 AND  sd.id IS NOT NULL THEN sd.value
                     WHEN db.removed=0 AND  gri.id IS NOT NULL AND gri.distributedAt IS NOT NULL THEN c.value
                     WHEN db.removed=0 AND  t.id IS NOT NULL AND t.money_received = 1 THEN CAST(SUBSTRING_INDEX(t.amount_sent, " ", -1) AS decimal(10, 2))
                 END AS usedItem

            FROM distribution_beneficiary db
            JOIN commodity c ON db.assistance_id=c.assistance_id
            JOIN assistance a ON db.assistance_id=a.id
            JOIN project p ON p.id=a.project_id AND p.iso3=:country
            -- smartcards
            LEFT JOIN smartcard_deposit sd ON sd.distribution_beneficiary_id=db.id
            -- mobile money
            LEFT JOIN transaction t ON t.distribution_beneficiary_id=db.id
            -- general reliefs
            LEFT JOIN general_relief_item gri ON gri.distribution_beneficiary_id=db.id
            WHERE db.assistance_id IN ('.implode(',', $assistanceBinds).') AND (sd.id IS NOT NULL OR gri.id IS NOT NULL OR t.id IS NOT NULL)
        ) AS counts
        GROUP BY assistance_id
        ';

        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(AssistanceStatistics::class, 'as');

        $qbr = $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('country', $countryIso3);

        foreach ($filter->getIds() as $key => $id) {
            $qbr->setParameter('assistance'.$key, $id);
        }

        return $qbr->getResult();

    }
}
