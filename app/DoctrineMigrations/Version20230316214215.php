<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230316214215 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
                ALTER TABLE user
                CHANGE phoneNumber phone_number VARCHAR(255) DEFAULT NULL,
                CHANGE phonePrefix phone_prefix VARCHAR(255) DEFAULT NULL,
                CHANGE changePassword change_password TINYINT(1) NOT NULL,
                CHANGE twoFactorAuthentication two_factor_authentication TINYINT(1) NOT NULL
                ');
    }

    public function down(Schema $schema): void
    {
       $this->abortIf(true, 'no downgrade');
    }
}
