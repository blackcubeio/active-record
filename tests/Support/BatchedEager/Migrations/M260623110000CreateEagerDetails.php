<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\BatchedEager\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * Migration to create the eagerDetails table (Owner hasOne/hasMany target).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M260623110000CreateEagerDetails implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('{{%eagerDetails}}', [
            'id' => ColumnBuilder::bigPrimaryKey(),
            'ownerId' => ColumnBuilder::bigint()->notNull(),
            'label' => ColumnBuilder::string(255)->notNull(),
        ]);

        $b->addForeignKey(
            '{{%eagerDetails}}',
            'fk-eagerDetails-ownerId',
            ['ownerId'],
            '{{%eagerOwners}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('{{%eagerDetails}}', 'fk-eagerDetails-ownerId');
        $b->dropTable('{{%eagerDetails}}');
    }
}
