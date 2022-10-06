<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201214141923 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            INSERT INTO modality_type (modality_id, name)
                SELECT id, "Winterization Kit"
                FROM modality
                WHERE name="In Kind" AND
                    NOT EXISTS(SELECT * FROM `modality_type` WHERE name ="Winterization Kit" AND modality_id=modality.id)
        '
        );

        $this->addSql('UPDATE modality_type SET name="Food Rations" WHERE name="Food" AND modality_id IN (SELECT id FROM modality WHERE name="In Kind")');
        $this->addSql('UPDATE modality_type SET name="Ready to Eat Rations" WHERE name="RTE Kit" AND modality_id IN (SELECT id FROM modality WHERE name="In Kind")');
        $this->addSql('UPDATE modality_type SET name="NFI Kit" WHERE name="Other NFI" AND modality_id IN (SELECT id FROM modality WHERE name="In Kind")');
    }

    public function down(Schema $schema): void
    {
    }
}
