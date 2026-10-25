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
 * Migration to create the eagerOwnerLabels junction table (Owner via Label).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class M260623130000CreateEagerOwnerLabels implements RevertibleMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $b->createTable('{{%eagerOwnerLabels}}', [
            'id' => ColumnBuilder::bigPrimaryKey(),
            'ownerId' => ColumnBuilder::bigint()->notNull(),
            'labelId' => ColumnBuilder::bigint()->notNull(),
        ]);

        $b->addForeignKey(
            '{{%eagerOwnerLabels}}',
            'fk-eagerOwnerLabels-ownerId',
            ['ownerId'],
            '{{%eagerOwners}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        $b->addForeignKey(
            '{{%eagerOwnerLabels}}',
            'fk-eagerOwnerLabels-labelId',
            ['labelId'],
            '{{%eagerLabels}}',
            ['id'],
            'CASCADE',
            'CASCADE'
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropForeignKey('{{%eagerOwnerLabels}}', 'fk-eagerOwnerLabels-labelId');
        $b->dropForeignKey('{{%eagerOwnerLabels}}', 'fk-eagerOwnerLabels-ownerId');
        $b->dropTable('{{%eagerOwnerLabels}}');
    }
}
