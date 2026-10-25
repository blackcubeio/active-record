<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support;

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;

/**
 * Trait for Cest classes that need database setup.
 *
 * Lifecycle per Cest:
 * 1. drop + create tables — once before the first test
 * 2. run all tests
 * 3. leave DB as-is
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait DatabaseCestTrait
{
    protected ConnectionInterface $db;

    private static array $setupDone = [];

    public function _before($I): void
    {
        $this->initializeDatabase();

        $className = static::class;
        if (isset(self::$setupDone[$className]) === false) {
            $this->createTables();
            self::$setupDone[$className] = true;
        }
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

        $this->db->createCommand('
            CREATE TABLE `testModels` (
                `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `active` BOOLEAN NOT NULL DEFAULT true,
                `languageId` VARCHAR(10) DEFAULT NULL
            )
        ')->execute();

        $this->db->createCommand('
            CREATE TABLE `testJoinModels` (
                `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
                `testModelId` BIGINT NOT NULL,
                `label` VARCHAR(255) NOT NULL,
                `active` BOOLEAN NOT NULL DEFAULT true,
                FOREIGN KEY (`testModelId`) REFERENCES `testModels`(`id`)
            )
        ')->execute();

        $this->db->createCommand('
            CREATE TABLE `testComposedModels` (
                `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `active` BOOLEAN NOT NULL DEFAULT true,
                `languageId` VARCHAR(10) DEFAULT NULL,
                `extraA` VARCHAR(255) DEFAULT NULL
            )
        ')->execute();
    }
}
