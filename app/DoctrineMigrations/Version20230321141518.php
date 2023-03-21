<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230321141518 extends AbstractMigration
{
    public const PRIVILEGES = [
        'moveAssistance' => ['ROLE_ADMIN', 'ROLE_COUNTRY_MANAGER', 'ROLE_PROJECT_MANAGER'],
    ];

    public function up(Schema $schema): void
    {
        foreach (self::PRIVILEGES as $privilege => $roles) {
            $this->addSql("INSERT INTO privilege (code) VALUES ('" . $privilege . "')");

            foreach ($roles as $role) {
                $this->addSql("INSERT INTO role_privilege (role_id, privilege_id)
                    SELECT role.id as role_id, privilege.id AS privilege_id FROM role, privilege
                    WHERE privilege.code = '" . $privilege . "'
                    AND role.code = '" . $role . "'
                ");
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::PRIVILEGES as $privilege => $roles) {
            $this->addSql("DELETE FROM privilege WHERE code = '" . $privilege . "'");

            foreach ($roles as $role) {
                $this->addSql("DELETE FROM role_privilege
                    WHERE role_id = (SELECT role.id FROM role WHERE role.code = '" . $role . "')
                    AND privilege_id = (SELECT privilege.id FROM privilege WHERE privilege.code = '" . $privilege . "')
                ");
            }
        }
    }
}
