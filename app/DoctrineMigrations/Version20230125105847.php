<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\MigrationException;
use JsonException;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230125105847 extends AbstractMigration
{
    const INSERT_LIMIT = 1000;

    private array $tableArrayColumns = [
        'project' => [
            'allowed_product_category_types' => [
                'nullable' => false
            ]
        ],
        'import_beneficiary_duplicity' => [
            'reasons' => [
                'nullable' => true
            ]
        ],
        'household' => [
            'assets' => [
                'nullable' => true
            ],
            'support_received_types' => [
                'nullable' => true
            ]
        ],
        'assistance' => [
            'allowed_product_category_types' => [
                'nullable' => false
            ]
        ],
    ];

    public function getDescription(): string
    {
        return 'Replace deprecated array type with json';
    }

    /**
     * @throws JsonException|Exception|MigrationException
     */
    public function preUp(Schema $schema): void
    {
        parent::preUp($schema);

        foreach ($this->tableArrayColumns as $table => $columns) {
            foreach ($columns as $column => $columnConfig) {
                $this->addSql(sprintf('ALTER TABLE %s ADD %s_temp JSON DEFAULT NULL AFTER %s', $table, $column, $column));
                $this->migrateSerializedArrayToJson($table, $column);
            }
        }
    }

    public function up(Schema $schema): void
    {
        foreach ($this->tableArrayColumns as $table => $columns) {
            foreach ($columns as $column => $columnConfig) {
                $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $column));
                $this->addSql(sprintf('ALTER TABLE %s CHANGE %s_temp %s JSON %s', $table, $column, $column, $this->getNullableValueStatement($columnConfig['nullable'])));
            }
        }
    }

    /**
     * @throws JsonException|Exception|MigrationException
     */
    public function preDown(Schema $schema): void
    {
        parent::preDown($schema);


        foreach ($this->tableArrayColumns as $table => $columns) {
            foreach ($columns as $column => $columnConfig) {
                $this->addSql(sprintf('ALTER TABLE %s ADD %s_temp LONGTEXT DEFAULT NULL AFTER %s', $table, $column, $column));
                $this->migrateJsonToSerializedArray($table, $column);
            }
        }
    }

    public function down(Schema $schema): void
    {
        foreach ($this->tableArrayColumns as $table => $columns) {
            foreach ($columns as $column => $columnConfig) {
                $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN %s', $table, $column));
                $this->addSql(sprintf('ALTER TABLE %s CHANGE %s_temp %s LONGTEXT %s', $table, $column, $column, $this->getNullableValueStatement($columnConfig['nullable'])));
            }
        }
    }

    /**
     * @throws JsonException|Exception
     */
    private function migrateSerializedArrayToJson(string $table, string $column): void
    {
        $count = $this->connection->fetchOne(sprintf('SELECT COUNT(id) FROM %s', $table));

        if ($count === 0) {
            return;
        }

        for ($i = 0; $i < 1 + intdiv($count, self::INSERT_LIMIT); $i++) {
            $this->addValuesUpdateStatements($column, $table, $i, true);
        }
    }

    /**
     * @throws JsonException|Exception
     */
    private function migrateJsonToSerializedArray(string $table, string $column)
    {
        $count = $this->connection->fetchOne(sprintf('SELECT COUNT(id) FROM %s', $table));

        if ($count === 0) {
            return;
        }

        for ($i = 0; $i < 1 + intdiv($count, self::INSERT_LIMIT); $i++) {
            $this->addValuesUpdateStatements($column, $table, $i, false);
        }
    }

    /**
     * @throws JsonException|Exception
     */
    public function addValuesUpdateStatements(string $column, string $table, int $step, bool $toJson): void
    {
        $sql = sprintf('SELECT id, %s FROM %s LIMIT %d OFFSET %d', $column, $table, self::INSERT_LIMIT, $step * self::INSERT_LIMIT);
        $updateStatements = [];

        $resultRows = $this->connection->fetchAllAssociative($sql);
        foreach ($resultRows as $result) {
            $id = (int) $result['id'];
            $databaseValue = $toJson ? $this->getJsonSqlValueFromSerializedArray($result[$column]) : $this->getSerializedArraySqlValueFromJson($result[$column]);

            $updateStatements[] = sprintf('UPDATE %s SET %s_temp = %s WHERE id = %d', $table, $column, $databaseValue, $id);
        }

        $this->addSql(implode(';', $updateStatements));
    }

    /**
     * @throws JsonException
     */
    private function getJsonSqlValueFromSerializedArray(string|null $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        $value = unserialize($value);
        $jsonValue = json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
        return sprintf('\'%s\'', $jsonValue);
    }

    /**
     * @throws JsonException
     */
    private function getSerializedArraySqlValueFromJson(string|null $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        $serializedArray = serialize($value);
        return sprintf('\'%s\'', $serializedArray);
    }

    private function getNullableValueStatement(bool $isNullable): string
    {
        if ($isNullable) {
            return 'DEFAULT NULL';
        }

        return 'NOT NULL';
    }
}
