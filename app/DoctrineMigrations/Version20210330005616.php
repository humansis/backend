<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210330005616 extends AbstractMigration
{
    private const ROLES = [
        'ROLE_REPORTING_READ',
        'ROLE_REPORTING_WRITE',
        'ROLE_PROJECT_MANAGEMENT_READ',
        'ROLE_PROJECT_MANAGEMENT_WRITE',
        'ROLE_PROJECT_MANAGEMENT_ASSIGN',
        'ROLE_BENEFICIARY_MANAGEMENT_READ',
        'ROLE_BENEFICIARY_MANAGEMENT_WRITE',
        'ROLE_USER_MANAGEMENT_READ',
        'ROLE_USER_MANAGEMENT_WRITE',
        'ROLE_AUTHORISE_PAYMENT',
        'ROLE_USER',
        'ROLE_DISTRIBUTION_CREATE',
        'ROLE_REPORTING',
        'ROLE_BENEFICIARY_MANAGEMENT',
        'ROLE_DISTRIBUTIONS_DIRECTOR',
        'ROLE_PROJECT_MANAGEMENT',
        'ROLE_USER_MANAGEMENT',
        'ROLE_REPORTING_COUNTRY',
        'ROLE_VENDOR',
        'ROLE_READ_ONLY',
        'ROLE_FIELD_OFFICER',
        'ROLE_PROJECT_OFFICER',
        'ROLE_PROJECT_MANAGER',
        'ROLE_COUNTRY_MANAGER',
        'ROLE_REGIONAL_MANAGER',
        'ROLE_ADMIN',
        'ROLE_ENUMERATOR',
    ];

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            'CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, deletable TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_57698A6A5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB'
        );

        $this->addSql(
            '
            CREATE TABLE user_role
            (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                INDEX IDX_2DE8C6A3A76ED395 (user_id),
                INDEX IDX_2DE8C6A3D60322AC (role_id),
                PRIMARY KEY (user_id, role_id),
                CONSTRAINT FK_2DE8C6A3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE,
                CONSTRAINT FK_2DE8C6A3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8
              COLLATE `UTF8_unicode_ci`
              ENGINE = InnoDB
        '
        );

        foreach (self::ROLES as $role) {
            $this->addSql("INSERT INTO role (name, deletable) VALUES (?, 0)", [$role]);

            $this->addSql(
                '
                INSERT INTO user_role (user_id, role_id)
                SELECT id, (SELECT id FROM role WHERE role.name=?)
                FROM user
                WHERE user.roles LIKE ?
            ',
                [$role, '%"' . $role . '"%']
            );
        }

        $this->addSql(
            '
            CREATE TABLE role_privilege
            (
                role_id      INT NOT NULL,
                privilege_id INT NOT NULL,
                INDEX IDX_D6D4495BD60322AC (role_id),
                INDEX IDX_D6D4495B32FB8AEA (privilege_id),
                PRIMARY KEY (role_id, privilege_id),
                CONSTRAINT FK_D6D4495BD60322AC FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE,
                CONSTRAINT FK_D6D4495B32FB8AEA FOREIGN KEY (privilege_id) REFERENCES privilege (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET UTF8
              COLLATE `UTF8_unicode_ci`
              ENGINE = InnoDB
        '
        );

        $this->addSql('ALTER TABLE user DROP roles');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_role');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE role_privilege');
        $this->addSql('ALTER TABLE `user` ADD roles LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\'');
    }
}
