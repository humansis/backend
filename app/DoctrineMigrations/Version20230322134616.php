<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230322134616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $roleId = $this->connection->executeQuery(
            "SELECT id FROM role where code='ROLE_PROJECT_OFFICER'"
        )->fetchOne();

        $privilegeId = $this->connection->executeQuery(
            "SELECT id FROM privilege where code='exportBeneficiaries'"
        )->fetchOne();

        if ($roleId === false || $privilegeId === false) {
            return;
        }

        $this->addSql(
            "INSERT INTO role_privilege (role_id, privilege_id) VALUES ($roleId, $privilegeId)"
        );
    }

    public function down(Schema $schema): void
    {
        $roleId = $this->connection->executeQuery(
            "SELECT id FROM role where code='ROLE_PROJECT_OFFICER'"
        )->fetchOne();

        $privilegeId = $this->connection->executeQuery(
            "SELECT id FROM privilege where code='exportBeneficiaries'"
        )->fetchOne();

        if ($roleId === false || $privilegeId === false) {
            return;
        }

        $this->addSql(
            "DELETE FROM role_privilege WHERE role_id=$roleId AND privilege_id=$privilegeId"
        );
    }
}
