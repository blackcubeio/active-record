<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic;

use Blackcube\ActiveRecord\Elastic\Migrations\M000000000000CreateElasticSchemas;
use Blackcube\ActiveRecord\Tests\Support\Elastic\Migrations\M241205120000CreateTags;
use Blackcube\ActiveRecord\Tests\Support\Elastic\Migrations\M241205130000CreateArticles;
use Blackcube\ActiveRecord\Tests\Support\Elastic\Migrations\M241205140000CreateProducts;
use Blackcube\ActiveRecord\Tests\Support\MysqlHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;

/**
 * Trait for Cest classes that need database setup.
 *
 * Lifecycle: drop + create tables before each test (elastic schemas affect model behavior).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait DatabaseCestTrait
{
    protected ConnectionInterface $db;

    public function _before($I): void
    {
        $this->initializeDatabase();
        $this->createTables();

        TestModel::clearSchemaCache();
        Article::clearSchemaCache();
        Product::clearSchemaCache();
        Tag::clearSchemaCache();
        CombinedTraitsModel::clearSchemaCache();
        MagicComposeModel::clearSchemaCache();

        $this->db->getSchema()->refresh();
        TestModelQuery::clearRealAttributesCache();
    }

    private function initializeDatabase(): void
    {
        $helper = new MysqlHelper();
        $this->db = $helper->createConnection();
        ConnectionProvider::set($this->db);
    }

    private function createTables(): void
    {
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
        try {
            $this->db->createCommand('DROP TABLE IF EXISTS `testJoinModels`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `testComposedModels`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `combinedModels`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `magicComposeModels`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `testModels`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `products`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `articles`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `tags`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `elasticSchemas`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `treeNodes`')->execute();
            $this->db->createCommand('DROP TABLE IF EXISTS `combinedModels`')->execute();
        } finally {
            $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
        }

        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());

        (new M000000000000CreateElasticSchemas())->up($builder);
        (new M241205120000CreateTags())->up($builder);
        (new M241205130000CreateArticles())->up($builder);
        (new M241205140000CreateProducts())->up($builder);

        $this->db->createCommand('
            CREATE TABLE `testModels` (
                `id` INT PRIMARY KEY AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `elasticSchemaId` BIGINT,
                `_extras` TEXT
            )
        ')->execute();

        $this->db->createCommand('
            CREATE TABLE `combinedModels` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `elasticSchemaId` BIGINT NULL,
                `_extras` JSON NULL,
                FOREIGN KEY (`elasticSchemaId`) REFERENCES `elasticSchemas`(`id`) ON DELETE SET NULL
            )
        ')->execute();

        $this->db->createCommand('
            CREATE TABLE `magicComposeModels` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `elasticSchemaId` BIGINT NULL,
                `_extras` JSON NULL,
                CONSTRAINT `fk_magicComposeModels_elasticSchemaId` FOREIGN KEY (`elasticSchemaId`) REFERENCES `elasticSchemas`(`id`) ON DELETE SET NULL
            )
        ')->execute();
    }
}
