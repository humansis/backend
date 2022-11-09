<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221005093304 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE beneficiary_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_eff2fb6ac9a6f20995ad07ce03fa7db8_idx (type), INDEX object_id_eff2fb6ac9a6f20995ad07ce03fa7db8_idx (object_id), INDEX discriminator_eff2fb6ac9a6f20995ad07ce03fa7db8_idx (discriminator), INDEX transaction_hash_eff2fb6ac9a6f20995ad07ce03fa7db8_idx (transaction_hash), INDEX blame_id_eff2fb6ac9a6f20995ad07ce03fa7db8_idx (blame_id), INDEX created_at_eff2fb6ac9a6f20995ad07ce03fa7db8_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE household_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_917348dd4727fff1e510242b4ae0def6_idx (type), INDEX object_id_917348dd4727fff1e510242b4ae0def6_idx (object_id), INDEX discriminator_917348dd4727fff1e510242b4ae0def6_idx (discriminator), INDEX transaction_hash_917348dd4727fff1e510242b4ae0def6_idx (transaction_hash), INDEX blame_id_917348dd4727fff1e510242b4ae0def6_idx (blame_id), INDEX created_at_917348dd4727fff1e510242b4ae0def6_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE person_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_907be00c9c366335b3359c1e8e2f6227_idx (type), INDEX object_id_907be00c9c366335b3359c1e8e2f6227_idx (object_id), INDEX discriminator_907be00c9c366335b3359c1e8e2f6227_idx (discriminator), INDEX transaction_hash_907be00c9c366335b3359c1e8e2f6227_idx (transaction_hash), INDEX blame_id_907be00c9c366335b3359c1e8e2f6227_idx (blame_id), INDEX created_at_907be00c9c366335b3359c1e8e2f6227_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smartcard_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_e1b9fa30aebc18dba94bfe9745e28500_idx (type), INDEX object_id_e1b9fa30aebc18dba94bfe9745e28500_idx (object_id), INDEX discriminator_e1b9fa30aebc18dba94bfe9745e28500_idx (discriminator), INDEX transaction_hash_e1b9fa30aebc18dba94bfe9745e28500_idx (transaction_hash), INDEX blame_id_e1b9fa30aebc18dba94bfe9745e28500_idx (blame_id), INDEX created_at_e1b9fa30aebc18dba94bfe9745e28500_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_e06395edc291d0719bee26fd39a32e8a_idx (type), INDEX object_id_e06395edc291d0719bee26fd39a32e8a_idx (object_id), INDEX discriminator_e06395edc291d0719bee26fd39a32e8a_idx (discriminator), INDEX transaction_hash_e06395edc291d0719bee26fd39a32e8a_idx (transaction_hash), INDEX blame_id_e06395edc291d0719bee26fd39a32e8a_idx (blame_id), INDEX created_at_e06395edc291d0719bee26fd39a32e8a_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE assistance_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_cdc95383564c4c01780e87a9d93f0672_idx (type), INDEX object_id_cdc95383564c4c01780e87a9d93f0672_idx (object_id), INDEX discriminator_cdc95383564c4c01780e87a9d93f0672_idx (discriminator), INDEX transaction_hash_cdc95383564c4c01780e87a9d93f0672_idx (transaction_hash), INDEX blame_id_cdc95383564c4c01780e87a9d93f0672_idx (blame_id), INDEX created_at_cdc95383564c4c01780e87a9d93f0672_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE assistance_relief_package_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_b42465f46dbd1b39d18575c0b3330435_idx (type), INDEX object_id_b42465f46dbd1b39d18575c0b3330435_idx (object_id), INDEX discriminator_b42465f46dbd1b39d18575c0b3330435_idx (discriminator), INDEX transaction_hash_b42465f46dbd1b39d18575c0b3330435_idx (transaction_hash), INDEX blame_id_b42465f46dbd1b39d18575c0b3330435_idx (blame_id), INDEX created_at_b42465f46dbd1b39d18575c0b3330435_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE import_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_7561f7cec2c2a7fbb0234f7be8a4764e_idx (type), INDEX object_id_7561f7cec2c2a7fbb0234f7be8a4764e_idx (object_id), INDEX discriminator_7561f7cec2c2a7fbb0234f7be8a4764e_idx (discriminator), INDEX transaction_hash_7561f7cec2c2a7fbb0234f7be8a4764e_idx (transaction_hash), INDEX blame_id_7561f7cec2c2a7fbb0234f7be8a4764e_idx (blame_id), INDEX created_at_7561f7cec2c2a7fbb0234f7be8a4764e_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smartcard_deposit_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_11747e94e006ec7da64508459e0ac15e_idx (type), INDEX object_id_11747e94e006ec7da64508459e0ac15e_idx (object_id), INDEX discriminator_11747e94e006ec7da64508459e0ac15e_idx (discriminator), INDEX transaction_hash_11747e94e006ec7da64508459e0ac15e_idx (transaction_hash), INDEX blame_id_11747e94e006ec7da64508459e0ac15e_idx (blame_id), INDEX created_at_11747e94e006ec7da64508459e0ac15e_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smartcard_purchase_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_0505d3a0bc1fa29da1aba556b2222e13_idx (type), INDEX object_id_0505d3a0bc1fa29da1aba556b2222e13_idx (object_id), INDEX discriminator_0505d3a0bc1fa29da1aba556b2222e13_idx (discriminator), INDEX transaction_hash_0505d3a0bc1fa29da1aba556b2222e13_idx (transaction_hash), INDEX blame_id_0505d3a0bc1fa29da1aba556b2222e13_idx (blame_id), INDEX created_at_0505d3a0bc1fa29da1aba556b2222e13_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE national_id_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_9364d157970333fcbd8adbd9d8834ab0_idx (type), INDEX object_id_9364d157970333fcbd8adbd9d8834ab0_idx (object_id), INDEX discriminator_9364d157970333fcbd8adbd9d8834ab0_idx (discriminator), INDEX transaction_hash_9364d157970333fcbd8adbd9d8834ab0_idx (transaction_hash), INDEX blame_id_9364d157970333fcbd8adbd9d8834ab0_idx (blame_id), INDEX created_at_9364d157970333fcbd8adbd9d8834ab0_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE smartcard_purchase_record_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_cafd1ab89eba12f8659edae7fd604ed4_idx (type), INDEX object_id_cafd1ab89eba12f8659edae7fd604ed4_idx (object_id), INDEX discriminator_cafd1ab89eba12f8659edae7fd604ed4_idx (discriminator), INDEX transaction_hash_cafd1ab89eba12f8659edae7fd604ed4_idx (transaction_hash), INDEX blame_id_cafd1ab89eba12f8659edae7fd604ed4_idx (blame_id), INDEX created_at_cafd1ab89eba12f8659edae7fd604ed4_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vendor_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_15479fb5e5135195f2fd56c351cfb2b8_idx (type), INDEX object_id_15479fb5e5135195f2fd56c351cfb2b8_idx (object_id), INDEX discriminator_15479fb5e5135195f2fd56c351cfb2b8_idx (discriminator), INDEX transaction_hash_15479fb5e5135195f2fd56c351cfb2b8_idx (transaction_hash), INDEX blame_id_15479fb5e5135195f2fd56c351cfb2b8_idx (blame_id), INDEX created_at_15479fb5e5135195f2fd56c351cfb2b8_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE distribution_beneficiary_audit (id INT UNSIGNED AUTO_INCREMENT NOT NULL, type VARCHAR(10) NOT NULL, object_id VARCHAR(255) NOT NULL, discriminator VARCHAR(255) DEFAULT NULL, transaction_hash VARCHAR(40) DEFAULT NULL, diffs LONGTEXT DEFAULT NULL, blame_id VARCHAR(255) DEFAULT NULL, blame_user VARCHAR(255) DEFAULT NULL, blame_user_fqdn VARCHAR(255) DEFAULT NULL, blame_user_firewall VARCHAR(100) DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX type_fd0ddfad4d3a877e79adc34b0eac51e7_idx (type), INDEX object_id_fd0ddfad4d3a877e79adc34b0eac51e7_idx (object_id), INDEX discriminator_fd0ddfad4d3a877e79adc34b0eac51e7_idx (discriminator), INDEX transaction_hash_fd0ddfad4d3a877e79adc34b0eac51e7_idx (transaction_hash), INDEX blame_id_fd0ddfad4d3a877e79adc34b0eac51e7_idx (blame_id), INDEX created_at_fd0ddfad4d3a877e79adc34b0eac51e7_idx (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE beneficiary_audit');
        $this->addSql('DROP TABLE household_audit');
        $this->addSql('DROP TABLE person_audit');
        $this->addSql('DROP TABLE smartcard_audit');
        $this->addSql('DROP TABLE user_audit');
        $this->addSql('DROP TABLE assistance_audit');
        $this->addSql('DROP TABLE assistance_relief_package_audit');
        $this->addSql('DROP TABLE import_audit');
        $this->addSql('DROP TABLE smartcard_deposit_audit');
        $this->addSql('DROP TABLE smartcard_purchase_audit');
        $this->addSql('DROP TABLE national_id_audit');
        $this->addSql('DROP TABLE smartcard_purchase_record_audit');
        $this->addSql('DROP TABLE vendor_audit');
        $this->addSql('DROP TABLE distribution_beneficiary_audit');
    }
}