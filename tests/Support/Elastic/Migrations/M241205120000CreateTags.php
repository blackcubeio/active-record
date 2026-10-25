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
 * Migration to create the tags table for testing.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M241205120000CreateTags implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('{{%tags}}', [
            'id' => ColumnBuilder::bigPrimaryKey(),
            'name' => ColumnBuilder::string(255)->notNull(),
            'elasticSchemaId' => ColumnBuilder::bigint(),
            '_extras' => ColumnBuilder::text(),
        ]);

        $b->addForeignKey(
            '{{%tags}}',
            'fk-tags-elasticSchemaId',
            ['elasticSchemaId'],
            '{{%elasticSchemas}}',
            ['id'],
            'SET NULL',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('{{%tags}}', 'fk-tags-elasticSchemaId');
        $b->dropTable('{{%tags}}');
    }
}
