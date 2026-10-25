<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Elastic\Migrations;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Schema\Column\ColumnBuilder;

/**
 * Migration to create the products table for testing custom column names.
 * Uses 'data' instead of '_extras' and 'sid' instead of 'elasticSchemaId'.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M241205140000CreateProducts implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('{{%products}}', [
            'id' => ColumnBuilder::bigPrimaryKey(),
            'name' => ColumnBuilder::string(255)->notNull(),
            'sid' => ColumnBuilder::bigint(),
            'data' => ColumnBuilder::text(),
        ]);

        $b->addForeignKey(
            '{{%products}}',
            'fk-products-sid',
            ['sid'],
            '{{%elasticSchemas}}',
            ['id'],
            'SET NULL',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('{{%products}}', 'fk-products-sid');
        $b->dropTable('{{%products}}');
    }
}
