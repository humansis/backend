<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201020070901 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE household SET livelihood=NULL');
        $this->addSql(
            'ALTER TABLE household
        CHANGE livelihood livelihood ENUM(
            \'daily_labour\',
            \'farming_argiculture\',
            \'firming_livestock\',
            \'government\',
            \'home_duties\',
            \'trading\',
            \'own_business\',
            \'textiles\'
        ) DEFAULT NULL COMMENT \'(DC2Type:enum_livelihood)\''
        );
    }

    public function down(Schema $schema): void
    {
    }
}
