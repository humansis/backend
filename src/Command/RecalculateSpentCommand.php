<?php

declare(strict_types=1);

namespace Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'recalculate:spent', description: 'Recalculate column spent in table assistance_relief_package for every row')]
class RecalculateSpentCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Recalculating spent column in assistance_relief_package table');

        $this->em->getConnection()->executeStatement('
            UPDATE `assistance_relief_package` a
            JOIN (
            SELECT
            arp.id AS aprid,
            sum(spr.value) AS total
            FROM assistance a

            -- get beneficiaries
            JOIN distribution_beneficiary db
            ON a.id = db.assistance_id
            JOIN assistance_relief_package arp
            ON db.id = arp.assistance_beneficiary_id
            AND arp.modality_type = \'Smartcard\'

            -- filter only smartcard with joined beneficiaries
            JOIN smartcard_purchase sp
            ON a.id = sp.assistance_id
            JOIN smartcard s
            ON sp.smartcard_id = s.id
            AND s.beneficiary_id = db.beneficiary_id

            JOIN smartcard_purchase_record spr
            ON sp.id = spr.smartcard_purchase_id

            GROUP by arp.id
            ) t ON t.aprid = a.id
            SET a.amount_spent = t.total
        ');

        $output->writeln('Recalculating spent column in assistance_relief_package table finished');

        return Command::SUCCESS;
    }
}
