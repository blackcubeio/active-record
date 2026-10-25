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
 * Migration to create the articles table for testing custom elastic column name.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M241205130000CreateArticles implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('{{%articles}}', [
            'id' => ColumnBuilder::bigPrimaryKey(),
            'title' => ColumnBuilder::string(255)->notNull(),
            'elasticSchemaId' => ColumnBuilder::bigint(),
            '_data' => ColumnBuilder::text(),
        ]);

        $b->addForeignKey(
            '{{%articles}}',
            'fk-articles-elasticSchemaId',
            ['elasticSchemaId'],
            '{{%elasticSchemas}}',
            ['id'],
            'SET NULL',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('{{%articles}}', 'fk-articles-elasticSchemaId');
        $b->dropTable('{{%articles}}');
    }
}
