<?php

namespace TransactionBundle\Repository;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Household;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use TransactionBundle\Entity\PurchasedItem;

class PurchasedItemRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param Beneficiary $beneficiary
     *
     * @return PurchasedItem[]
     */
    public function getPurchases(Beneficiary $beneficiary): iterable
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(PurchasedItem::class, 'p');

        $sql = '
        SELECT '.$rsm->generateSelectClause().' FROM (
            SELECT max(sp.used_at) as used_at, spr.product_id, p.name, p.unit, sum(spr.value) as value, sum(spr.quantity) as quantity, \'Smartcard\' as source
                FROM smartcard_purchase_record spr
                LEFT JOIN smartcard_purchase sp ON sp.id=spr.smartcard_purchase_id
                LEFT JOIN smartcard s ON s.id=sp.smartcard_id AND s.beneficiary_id = :beneficiary
                LEFT JOIN product p ON p.id=spr.product_id
                GROUP BY spr.product_id
            UNION
            SELECT max(vp.used_at) as used_at, vpr.product_id, p.name, p.unit, sum(vpr.value) as value, sum(vpr.quantity) as quantity, \'QRvoucher\' as source
                FROM voucher_purchase_record vpr
                LEFT JOIN voucher_purchase vp ON vp.id=vpr.voucher_purchase_id
                LEFT JOIN voucher v ON v.voucher_purchase_id=vp.id
                LEFT JOIN booklet b ON b.id=v.booklet_id
                JOIN distribution_beneficiary db ON db.id=b.distribution_beneficiary_id AND db.beneficiary_id = :beneficiary
                LEFT JOIN product p ON p.id=vpr.product_id
                GROUP BY vpr.product_id
            ) AS p
        ';

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('beneficiary', $beneficiary->getId())
            ->getResult();
    }

    /**
     * @param Household $household
     *
     * @return PurchasedItem[]
     */
    public function getHouseholdPurchases(Household $household): iterable
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(PurchasedItem::class, 'p');

        $sql = '
        SELECT '.$rsm->generateSelectClause().' FROM (
            SELECT spr.id, sp.used_at, s.beneficiary_id, spr.product_id, p.name, p.unit, spr.value, spr.currency, spr.quantity, \'Smartcard\' as source
                FROM smartcard_purchase_record spr
                JOIN smartcard_purchase sp ON sp.id=spr.smartcard_purchase_id
                JOIN smartcard s ON s.id=sp.smartcard_id
                JOIN beneficiary b ON b.id=s.beneficiary_id AND b.household_id = :household
                JOIN product p ON p.id=spr.product_id
            UNION
            SELECT vpr.id, vp.used_at, db.beneficiary_id, vpr.product_id, p.name, p.unit, vpr.value, b.currency, vpr.quantity, \'QRvoucher\' as source
                FROM voucher_purchase_record vpr
                JOIN voucher_purchase vp ON vp.id=vpr.voucher_purchase_id
                JOIN voucher v ON v.voucher_purchase_id=vp.id
                JOIN booklet b ON b.id=v.booklet_id
                JOIN distribution_beneficiary db ON db.id=b.distribution_beneficiary_id
                JOIN beneficiary bnf ON bnf.id=db.beneficiary_id AND bnf.household_id = :household
                JOIN product p ON p.id=vpr.product_id
            ) AS p ORDER BY used_at ASC
        ';

        return $this->getEntityManager()
            ->createNativeQuery($sql, $rsm)
            ->setParameter('household', $household->getId())
            ->getResult();
    }
}
