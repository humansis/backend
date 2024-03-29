<?php

declare(strict_types=1);

namespace Utils;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Entity\Project;
use Entity\Booklet;
use Exception;

class BookletGenerator
{
    public function __construct(private readonly EntityManager $em)
    {
    }

    public function generate(
        Project $project,
        string $countryIso3,
        int $numberOfBooklets,
        int $numberOfVouchers,
        string $currency,
        array $values,
        ?string $password = null
    ): void {
        $code = $countryIso3 . '_' . $project->getName() . '_' . date('d-m-Y') . '_booklet';

        try {
            $this->em->beginTransaction();

            $lastCode = $this->findBookletCode($code);
            $lastBatchNumber = $lastCode ? (int) substr($lastCode, strlen($code)) : 0;

            $firstBookletId = $this->generateBooklets(
                $lastBatchNumber,
                $project,
                $code,
                $countryIso3,
                $numberOfBooklets,
                $numberOfVouchers,
                $currency,
                $password
            );

            $this->generateVouchers($values, $numberOfVouchers, $firstBookletId);

            $this->em->commit();
        } catch (Exception $exception) {
            $this->em->rollback();
            throw $exception;
        }
    }

    /**
     * Find last booklet code similar to $code, if exists.
     *
     *
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function findBookletCode(string $code): ?string
    {
        $code = $this->em->getConnection()
            ->executeQuery('SELECT `code` FROM booklet WHERE `code` LIKE ? ORDER BY id DESC LIMIT 1', [$code . '%'])
            ->fetchOne();

        return $code ?: null;
    }

    /**
     * Generate set of booklets since $lastBatchNumber.
     *
     *
     * @throws DBALException
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
    ): int {
        $this->em->getConnection()
            ->executeQuery(
                '
                INSERT INTO booklet (`code`, `number_vouchers`, `currency`, `password`, `iso3`, `project_id`, `status`)
                WITH RECURSIVE sequence AS (
                    SELECT 1 AS level
                    UNION ALL
                    SELECT level + 1 AS value FROM sequence WHERE sequence.level < ?
                )
                SELECT CONCAT(?, LPAD(level + ?, 6, "0")), ?, ?, ?, ?, ?, ? FROM sequence',
                [
                    $numberOfBooklets,
                    $code,
                    $lastBatchNumber,
                    $numberOfVouchers,
                    $currency,
                    $password,
                    $countryIso3,
                    $project->getId(),
                    Booklet::UNASSIGNED,
                ]
            );

        return (int) $this->em->getConnection()->lastInsertId();
    }

    /**
     * Generate vouchers for booklets >= $bookletId.
     *
     *
     * @throws DBALException
     */
    private function generateVouchers(array $values, int $numberOfVouchers, int $bookletId)
    {
        $sqlSnippet = '';
        for ($i = 0; $i < count($values); ++$i) {
            if (0 === $i) {
                $sqlSnippet .= ' SELECT 1 AS level, CAST(? AS UNSIGNED) AS val';
                if (1 === count($values)) {
                    $sqlSnippet .= ' UNION ALL SELECT level + 1 AS level, val FROM sequence WHERE sequence.level < ' . $numberOfVouchers;
                }
            } elseif ($i < count($values) - 1) {
                $sqlSnippet .= ' UNION ALL SELECT level + 1 AS level, ? AS val FROM sequence WHERE sequence.level = ' . $i;
            } else {
                $sqlSnippet .= ' UNION ALL SELECT level + 1 AS level, ? AS val FROM sequence WHERE sequence.level BETWEEN ' . $i . ' AND ' . ($numberOfVouchers - 1);
            }
        }

        // Voucher code contains its own primary ID. So, in first step, we generate vouchers with some placeholder.
        // In second step, we replace placeholder with correct primary ID.

        $this->em->getConnection()
            ->executeQuery(
                '
                INSERT INTO voucher (`value`, `booklet_id`, `code`)
                WITH RECURSIVE sequence AS (
                    ' . $sqlSnippet . '
                )
                SELECT
                    s.val,
                    b.id,
                    -- code = {currency}{value}*{booklet_code}-{primary_id_of_current_voucher}-{password}
                    CONCAT(b.currency, s.val, "*", b.code, (@cnt := @cnt + 1), IF(b.password, CONCAT("-", b.password), ""))
                FROM sequence s, booklet b
                CROSS JOIN (SELECT @cnt := 0) AS dummy
                WHERE b.id >= ?',
                array_merge($values, [$bookletId])
            );
    }
}
