<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticMigration;

use Blackcube\ActiveRecord\Elastic\Migrations\M000000000000CreateElasticSchemas;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ElasticMigrationTester;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;

class MigrationCest
{
    use DatabaseCestTrait;

    private function ensureTestData(): void
    {
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        try {
            $tables = $this->db->createCommand('SHOW TABLES')->queryColumn();
            foreach ($tables as $table) {
                $this->db->createCommand('DROP TABLE IF EXISTS `' . $table . '`')->execute();
            }
        } finally {
            $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }
    }

    public function testMigrationUp(ElasticMigrationTester $I): void
    {
        $this->ensureTestData();

        $migration = new M000000000000CreateElasticSchemas();
        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());

        $migration->up($builder);

        $tableSchema = $this->db->getTableSchema('elasticSchemas');
        $I->assertNotNull($tableSchema);

        $columns = $tableSchema->getColumns();
        $I->assertArrayHasKey('id', $columns);
        $I->assertArrayHasKey('name', $columns);
        $I->assertArrayHasKey('schema', $columns);
        $I->assertArrayHasKey('view', $columns);
        $I->assertArrayHasKey('dateCreate', $columns);
        $I->assertArrayHasKey('dateUpdate', $columns);
    }

    public function testMigrationDown(ElasticMigrationTester $I): void
    {
        $this->ensureTestData();

        $migration = new M000000000000CreateElasticSchemas();
        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());

        $migration->up($builder);
        $migration->down($builder);

        $tableSchema = $this->db->getTableSchema('elasticSchemas', true);
        $I->assertNull($tableSchema);
    }

    public function testMigrationUpDown(ElasticMigrationTester $I): void
    {
        $this->ensureTestData();

        $migration = new M000000000000CreateElasticSchemas();
        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());

        $migration->up($builder);
        $I->assertNotNull($this->db->getTableSchema('elasticSchemas'));

        $migration->down($builder);
        $I->assertNull($this->db->getTableSchema('elasticSchemas', true));

        $migration->up($builder);
        $I->assertNotNull($this->db->getTableSchema('elasticSchemas', true));
    }
}
