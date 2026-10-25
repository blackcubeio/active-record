<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Tests\Support\Hazeltree\Migrations\M241205120000CreateTreeNodes;
use Blackcube\ActiveRecord\Tests\Support\MysqlHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;

/**
 * Trait for Cest classes that need database setup.
 *
 * Lifecycle: drop + create tables before each test (tree operations are stateful).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait DatabaseCestTrait
{
    protected ConnectionInterface $db;
    protected TreeNodeFactory $factory;

    public function _before($I): void
    {
        $this->initializeDatabase();
        $this->factory = new TreeNodeFactory($this->db);
        $this->createTables();
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
        $this->db->createCommand('DROP TABLE IF EXISTS `testJoinModels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `testComposedModels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `testModels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `combinedModels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `magicComposeModels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `products`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `articles`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `tags`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `elasticSchemas`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `treeNodes`')->execute();
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();

        $migration = new M241205120000CreateTreeNodes();
        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());
        $migration->up($builder);

        $this->db->createCommand('
            CREATE TABLE `combinedModels` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `path` VARCHAR(255) NOT NULL,
                `left` DECIMAL(25,22) NOT NULL,
                `right` DECIMAL(25,22) NOT NULL,
                `level` INT NOT NULL,
                INDEX idx_cm_left (`left`),
                INDEX idx_cm_right (`right`),
                INDEX idx_cm_level (`level`)
            )
        ')->execute();
    }
}
