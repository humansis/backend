<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220804085019 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('START TRANSACTION');
        $this->addSql(
            "INSERT INTO `user` (`id`, `username`, `username_canonical`, `email`, `email_canonical`, `enabled`, `salt`, `password`, `last_login`, `confirmation_token`, `password_requested_at`, `language`, `vendor_id`, `changePassword`, `phonePrefix`, `phoneNumber`, `twoFactorAuthentication`) VALUES (NULL, 'Migration Validation user', 'Migration Validation user', 'migration.validation.user@example.org', 'migration.validation.user@example.org', '0', NULL, '', NULL, NULL, NULL, NULL, NULL, '0', NULL, NULL, '0')"
        );
        $this->addSql('SET @last_id = LAST_INSERT_ID()');
        $this->addSql('CREATE TEMPORARY TABLE validated_migration SELECT a.id FROM assistance a WHERE a.validated = 1');
        $this->addSql('ALTER TABLE assistance ADD validated_by_id INT DEFAULT NULL, DROP validated');
        $this->addSql('ALTER TABLE assistance ADD CONSTRAINT FK_1B4F85F2C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_1B4F85F2C69DE5E5 ON assistance (validated_by_id)');
        $this->addSql('UPDATE assistance a INNER JOIN validated_migration vm ON vm.id = a.id SET a.validated_by_id = @last_id');
        $this->addSql('COMMIT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('START TRANSACTION');
        $this->addSql('CREATE TEMPORARY TABLE validated_migration SELECT a.id FROM assistance a WHERE a.validated_by_id IS NOT NULL');
        $this->addSql('ALTER TABLE assistance DROP FOREIGN KEY FK_1B4F85F2C69DE5E5');
        $this->addSql('DROP INDEX IDX_1B4F85F2C69DE5E5 ON assistance');
        $this->addSql('ALTER TABLE assistance ADD validated TINYINT(1) DEFAULT \'0\' NOT NULL, DROP validated_by_id');
        $this->addSql('DELETE FROM user u WHERE u.email=\'migration.validation.user@example.org\'');
        $this->addSql('UPDATE assistance a INNER JOIN validated_migration vm ON vm.id = a.id SET a.validated = \'1\'');
        $this->addSql('COMMIT');
    }
}
