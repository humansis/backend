<?php
declare(strict_types=1);

namespace NewApiBundle\Utils;

use Doctrine\ORM\EntityManager;
use NewApiBundle\Entity\Project;
use NewApiBundle\Entity\Booklet;

class BookletGenerator
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function generate(
        Project $project,
        string $countryIso3,
        int $numberOfBooklets,
        int $numberOfVouchers,
        string $currency,
        array $values,
        ?string $password = null
    ): void
    {
        $code = $countryIso3.'_'.$project->getName().'_'.date('d-m-Y').'_booklet';

        try {
            $this->em->beginTransaction();
            $this->em->getConnection()->executeQuery('ALTER TABLE `voucher` DROP INDEX `UNIQ_1392A5D877153098`');

            $lastCode = $this->findBookletCode($code);
            $lastBatchNumber = $lastCode ? (int) substr($lastCode, strlen($code)) : 0;

            $firstBookletId = $this->generateBooklets(
                $lastBatchNumber, $project, $code, $countryIso3, $numberOfBooklets, $numberOfVouchers, $currency, $password
            );

            $this->generateVouchers($values, $numberOfVouchers, $firstBookletId);

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();
            throw $exception;
        } finally {
            $this->em->getConnection()->executeQuery('ALTER TABLE `voucher` ADD UNIQUE INDEX UNIQ_1392A5D877153098 (code)');
        }
    }

    /**
     * Find last booklet code similar to $code, if exists.
     *
     * @param string $code
     *
     * @return string|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function findBookletCode(string $code): ?string
    {
        $code = $this->em->getConnection()
            ->executeQuery('SELECT `code` FROM booklet WHERE `code` LIKE ? ORDER BY id DESC LIMIT 1', [$code.'%'])
            ->fetchColumn();

        return $code ?: null;
    }

    /**
     * Generate set of booklets since $lastBatchNumber.
     *
     * @param int         $lastBatchNumber
     * @param Project     $project
     * @param string      $code
     * @param string      $countryIso3
     * @param int         $numberOfBooklets
     * @param int         $numberOfVouchers
     * @param string      $currency
     * @param string|null $password
     *
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function generateBooklets(
        int $lastBatchNumber,
        Project $project,
        string $code,
        string $countryIso3,
        int $numberOfBooklets,
        int $numberOfVouchers,
        string $currency,
        ?string $password = null
    ): int
    {
        $this->em->getConnection()
            ->executeQuery('
                INSERT INTO booklet (`code`, `number_vouchers`, `currency`, `password`, `country_iso3`, `project_id`, `status`) 
                WITH RECURSIVE sequence AS (
                    SELECT 1 AS level
                    UNION ALL
                    SELECT level + 1 AS value FROM sequence WHERE sequence.level < ?
                )
                SELECT CONCAT(?, LPAD(level + ?, 6, "0")), ?, ?, ?, ?, ?, ? FROM sequence', [
                $numberOfBooklets,
                $code,
                $lastBatchNumber,
                $numberOfVouchers,
                $currency,
                $password,
                $countryIso3,
                $project->getId(),
                Booklet::UNASSIGNED,
            ]);

        return (int) $this->em->getConnection()->lastInsertId();
    }

    /**
     * Generate vouchers for booklets >= $bookletId.
     *
     * @param array $values
     * @param int   $numberOfVouchers
     * @param int   $bookletId
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function generateVouchers(array $values, int $numberOfVouchers, int $bookletId)
    {
        $sqlSnippet = '';
        for ($i = 0; $i < count($values); ++$i) {
            if (0 === $i) {
                $sqlSnippet .= ' SELECT 1 AS level, CAST(? AS UNSIGNED) AS val';
                if (1 === count($values)) {
                    $sqlSnippet .= ' UNION ALL SELECT level + 1 AS level, val FROM sequence WHERE sequence.level < '.$numberOfVouchers;
                }
            } elseif ($i < count($values) - 1) {
                $sqlSnippet .= ' UNION ALL SELECT level + 1 AS level, ? AS val FROM sequence WHERE sequence.level = '.$i;
            } else {
                $sqlSnippet .= ' UNION ALL SELECT level + 1 AS level, ? AS val FROM sequence WHERE sequence.level BETWEEN '.$i.' AND '.($numberOfVouchers - 1);
            }
        }

        // Voucher code contains its own primary ID. So, in first step, we generate vouchers with some placeholder.
        // In second step, we replace placeholder with correct primary ID.

        $this->em->getConnection()
            ->executeQuery('
                INSERT INTO voucher (`value`, `booklet_id`, `code`)
                WITH RECURSIVE sequence AS (
                    '.$sqlSnippet.'
                )
                SELECT
                    s.val,
                    b.id,
                    -- code = {currency}{value}*{booklet_code}-{primary_id_of_current_voucher}-{password}
                    CONCAT(b.currency, s.val, "*", b.code, "-ID_PLACEHOLDER", IF(b.password, CONCAT("-", b.password), ""))
                FROM sequence s, booklet b
                WHERE b.id >= ?', array_merge($values, [$bookletId]));

        $this->em->getConnection()->executeQuery('UPDATE voucher SET code=REPLACE(code, "ID_PLACEHOLDER", id) WHERE id >= LAST_INSERT_ID()');
    }
}
