<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210422174825 extends AbstractMigration
{
    private const ROLES = [
        'ROLE_REPORTING_READ' => 'Reporting Read',
        'ROLE_REPORTING_WRITE' => 'Reporting Write',
        'ROLE_PROJECT_MANAGEMENT_READ' => 'Project Management Read',
        'ROLE_PROJECT_MANAGEMENT_WRITE' => 'Project Management Write',
        'ROLE_PROJECT_MANAGEMENT_ASSIGN' => 'Project Management Assign',
        'ROLE_BENEFICIARY_MANAGEMENT_READ' => 'Beneficiary Management Read',
        'ROLE_BENEFICIARY_MANAGEMENT_WRITE' => 'Beneficiary Management Write',
        'ROLE_USER_MANAGEMENT_READ' => 'User Management Read',
        'ROLE_USER_MANAGEMENT_WRITE' => 'User Management Write',
        'ROLE_AUTHORISE_PAYMENT' => 'Authorise Payment',
        'ROLE_USER' => 'User',
        'ROLE_DISTRIBUTION_CREATE' => 'Distribution Create',
        'ROLE_REPORTING' => 'Reporting',
        'ROLE_BENEFICIARY_MANAGEMENT' => 'Beneficiary Management',
        'ROLE_DISTRIBUTIONS_DIRECTOR' => 'Distributions Director',
        'ROLE_PROJECT_MANAGEMENT' => 'Project Management',
        'ROLE_USER_MANAGEMENT' => 'User Management',
        'ROLE_REPORTING_COUNTRY' => 'Reporting Country',
        'ROLE_VENDOR' => 'Vendor',
        'ROLE_READ_ONLY' => 'Read Only',
        'ROLE_FIELD_OFFICER' => 'Field Officer',
        'ROLE_PROJECT_OFFICER' => 'Project Officer',
        'ROLE_PROJECT_MANAGER' => 'Project Manager',
        'ROLE_COUNTRY_MANAGER' => 'Country Manager',
        'ROLE_REGIONAL_MANAGER' => 'Regional Manager',
        'ROLE_ADMIN' => 'Admin',
        'ROLE_ENUMERATOR' => 'Enumerator',
    ];

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_57698A6A5E237E06 ON role');
        $this->addSql(
            'ALTER TABLE role
                            RENAME COLUMN name TO code,
                            ADD name VARCHAR(255) NOT NULL'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57698A6A77153098 ON role (code)');

        foreach (self::ROLES as $code => $name) {
            $this->addSql('UPDATE role SET name=? WHERE code=?', [$name, $code]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
