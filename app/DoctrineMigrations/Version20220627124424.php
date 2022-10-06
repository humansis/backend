<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220627124424 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "alter table household modify livelihood enum ('irregular_earnings', 'farming_agriculture', 'farming_livestock', 'regular_salary_private', 'regular_salary_public', 'social_welfare', 'pension', 'home_duties', 'own_business_trading', 'savings', 'remmitances', 'humanitarian_aid', 'no_income', 'refused_to_answer', 'other') null comment '(DC2Type:enum_livelihood)';"
        );
        $this->addSql(
            'ALTER TABLE import_queue CHANGE state state ENUM(\'New\', \'Valid\', \'Invalid\', \'Invalid Exported\', \'Identity Candidate\', \'Unique Candidate\', \'Similarity Candidate\', \'To Create\', \'To Update\', \'To Link\', \'To Ignore\', \'Created\', \'Updated\', \'Linked\', \'Ignored\', \'Error\') NOT NULL COMMENT \'(DC2Type:enum_import_queue_state)\''
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(true, 'Nope.');
    }
}
