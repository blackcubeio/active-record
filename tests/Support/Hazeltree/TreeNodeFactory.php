<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Support\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\Helpers\TreeHelper;
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Factory to create TreeNode entries directly in the database for testing.
 * Uses TreeHelper to calculate left/right/level values from path.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class TreeNodeFactory
{
    public function __construct(
        private ConnectionInterface $db
    ) {
    }

    /**
     * Insert a node directly into the database.
     *
     * @param string $name Node name
     * @param string $path Node path in dot notation (e.g., "1.2.3")
     * @return int The inserted node ID
     */
    public function insert(string $name, string $path): int
    {
        $matrix = TreeHelper::convert($path);
        $left = TreeHelper::getLeft($matrix);
        $right = TreeHelper::getRight($matrix);
        $level = TreeHelper::getLevel($path);

        $this->db->createCommand()->insert('{{%treeNodes}}', [
            'name' => $name,
            'path' => $path,
            'left' => $left,
            'right' => $right,
            'level' => $level,
        ])->execute();

        return (int) $this->db->getLastInsertID();
    }

    /**
     * Get a TreeNode by ID.
     */
    public function findById(int $id): ?TreeNode
    {
        return TreeNode::query()->where(['id' => $id])->one();
    }
}
