<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\BatchedEager;

use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Migrations\M260623100000CreateEagerOwners;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Migrations\M260623110000CreateEagerDetails;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Migrations\M260623120000CreateEagerLabels;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Migrations\M260623130000CreateEagerOwnerLabels;
use Blackcube\ActiveRecord\Tests\Support\MysqlHelper;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;

/**
 * Database setup for the BatchedEager suite: owners + details (hasOne/hasMany)
 * + labels through the ownerLabels junction (via).
 *
 * Tables are dropped + recreated through migrations before each test, so every
 * test starts on a clean schema and seeds its own data.
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
        $this->db->createCommand('DROP TABLE IF EXISTS `eagerOwnerLabels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `eagerLabels`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `eagerDetails`')->execute();
        $this->db->createCommand('DROP TABLE IF EXISTS `eagerOwners`')->execute();
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();

        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());
        (new M260623100000CreateEagerOwners())->up($builder);
        (new M260623110000CreateEagerDetails())->up($builder);
        (new M260623120000CreateEagerLabels())->up($builder);
        (new M260623130000CreateEagerOwnerLabels())->up($builder);

        $this->db->getSchema()->refresh();
    }
}
