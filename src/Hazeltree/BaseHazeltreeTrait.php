<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\Exceptions\InvalidItemConfigurationException;
use Blackcube\ActiveRecord\Hazeltree\Helpers\Matrix;
use Blackcube\ActiveRecord\Hazeltree\Helpers\TreeHelper;

/**
 * Base trait implementing the Hazeltree nested set algorithm using rational numbers.
 *
 * All methods are prefixed with "hazeltree" or "tryHazeltree" to avoid collisions.
 * This trait is designed to be used inside a composite trait (HazeltreeTrait,
 * HazeltreeElasticTrait) that provides the dispatch layer (__get/__set/etc.).
 *
 * The model using this trait must have database columns for:
 * - path (VARCHAR) - dot notation path (e.g., "1.2.3")
 * - left (FLOAT) - left boundary value
 * - right (FLOAT) - right boundary value
 * - level (INT) - depth level (1 = root)
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BaseHazeltreeTrait
{
    /**
     * @var float|null Left boundary value
     */
    private ?float $left = null;

    /**
     * @var float|null Right boundary value
     */
    private ?float $right = null;

    /**
     * @var string|null Path in dot notation
     */
    private ?string $path = null;

    /**
     * @var int|null Level (depth) in tree
     */
    private ?int $level = null;

    /**
     * @var Matrix|null Cached node matrix
     */
    private ?Matrix $nodeMatrix = null;

    /**
     * @var bool System-level protection flag (toggled by populateRecord/refresh)
     */
    private bool $hazelTreeSystemProtected = true;

    /**
     * @var bool Developer override flag (toggled by protectHazeltree)
     */
    private bool $hazelTreeDevOverride = false;

    /**
     * Check if tree properties are currently protected.
     */
    private function isHazeltreeProtected(): bool
    {
        $isProtected = $this->hazelTreeSystemProtected;
        if ($this->hazelTreeDevOverride === true) {
            $isProtected = false;
        }
        return $isProtected;
    }

    /**
     * Allow developer to temporarily disable tree property protection.
     *
     * @param bool $protect True to enable protection, false to disable
     */
    public function protectHazeltree(bool $protect): void
    {
        $this->hazelTreeDevOverride = $protect === false;
    }

    /**
     * Column name for left boundary.
     * Override to customize.
     */
    public function leftColumn(): string
    {
        return 'left';
    }

    /**
     * Column name for right boundary.
     * Override to customize.
     */
    public function rightColumn(): string
    {
        return 'right';
    }

    /**
     * Column name for path.
     * Override to customize.
     */
    public function pathColumn(): string
    {
        return 'path';
    }

    /**
     * Column name for level.
     * Override to customize.
     */
    public function levelColumn(): string
    {
        return 'level';
    }

    /**
     * Check if a property name is a tree property.
     */
    private function isHazeltreeProperty(string $name): bool
    {
        return $name === $this->leftColumn()
            || $name === $this->rightColumn()
            || $name === $this->pathColumn()
            || $name === $this->levelColumn();
    }

    /**
     * Try to get a tree property value.
     * @return bool true if handled
     */
    protected function tryHazeltreeGet(string $name, mixed &$result): bool
    {
        $isHandled = false;
        if ($this->isHazeltreeProperty($name) === true) {
            $result = match ($name) {
                $this->leftColumn() => $this->left,
                $this->rightColumn() => $this->right,
                $this->pathColumn() => $this->path,
                $this->levelColumn() => $this->level,
                default => null,
            };
            $isHandled = true;
        }
        return $isHandled;
    }

    /**
     * Try to set a tree property value.
     * @return bool true if handled
     */
    protected function tryHazeltreeSet(string $name, mixed $value): bool
    {
        $isHandled = false;
        if ($this->isHazeltreeProperty($name) === true) {
            if ($this->isHazeltreeProtected() === true) {
                throw new \Error(
                    sprintf('Cannot set read-only tree property %s::$%s. Use saveInto(), saveBefore() or saveAfter(), or protectHazeltree(false).', static::class, $name)
                );
            }
            $this->protectHazeltreeInternal(false);
            $this->populateProperty($name, $value);
            $this->protectHazeltreeInternal(true);
            $isHandled = true;
        }
        return $isHandled;
    }

    /**
     * Try to check if a tree property is set.
     * @return bool true if handled
     */
    protected function tryHazeltreeIsset(string $name, bool &$result): bool
    {
        $isHandled = false;
        if ($this->isHazeltreeProperty($name) === true) {
            $result = match ($name) {
                $this->leftColumn() => $this->left !== null,
                $this->rightColumn() => $this->right !== null,
                $this->pathColumn() => $this->path !== null,
                $this->levelColumn() => $this->level !== null,
                default => false,
            };
            $isHandled = true;
        }
        return $isHandled;
    }

    /**
     * Try to handle a magic method call for tree properties.
     * @return bool true if handled
     */
    protected function tryHazeltreeCall(string $name, array $arguments, mixed &$result): bool
    {
        $isHandled = false;
        if (str_starts_with($name, 'get') === true && strlen($name) > 3) {
            $property = lcfirst(substr($name, 3));
            if ($this->isHazeltreeProperty($property) === true) {
                $this->tryHazeltreeGet($property, $result);
                $isHandled = true;
            }
        }

        if ($isHandled === false && str_starts_with($name, 'set') === true && strlen($name) > 3) {
            $property = lcfirst(substr($name, 3));
            if ($this->isHazeltreeProperty($property) === true) {
                if ($this->isHazeltreeProtected() === true) {
                    throw new \Error(
                        sprintf('Cannot set read-only tree property %s::$%s. Use saveInto(), saveBefore() or saveAfter(), or protectHazeltree(false).', static::class, $property)
                    );
                }
            }
        }

        return $isHandled;
    }

    /**
     * Include tree columns in property values for ActiveRecord save.
     * If path is null, automatically finds the next available root path.
     *
     * @param array $values Current property values from parent
     * @return array Merged values including tree columns
     */
    protected function hazeltreePropertyValuesInternal(array $values): array
    {
        if ($this->path === null) {
            $nextRootPath = $this->getNextRootPath();
            $this->setNodePath($nextRootPath);
        }

        $values[$this->leftColumn()] = $this->left;
        $values[$this->rightColumn()] = $this->right;
        $values[$this->pathColumn()] = $this->path;
        $values[$this->levelColumn()] = $this->level;
        return $values;
    }

    /**
     * Try to populate a tree property.
     * @return bool true if handled
     */
    protected function hazeltreePopulateProperty(string $name, mixed $value): bool
    {
        $isHandled = false;
        if ($this->isHazeltreeProperty($name) === true) {
            if ($this->isHazeltreeProtected() === true) {
                throw new \Error(
                    sprintf('Cannot set read-only tree property %s::$%s. Use saveInto(), saveBefore() or saveAfter(), or protectHazeltree(false).', static::class, $name)
                );
            }
            match ($name) {
                $this->leftColumn() => $this->left = $value !== null ? (float) $value : null,
                $this->rightColumn() => $this->right = $value !== null ? (float) $value : null,
                $this->pathColumn() => $this->setPathInternal($value),
                $this->levelColumn() => $this->level = $value !== null ? (int) $value : null,
                default => null,
            };
            $isHandled = true;
        }
        return $isHandled;
    }

    /**
     * Disable protection before populateRecord.
     */
    protected function beforeHazeltreePopulateRecord(): void
    {
        $this->protectHazeltreeInternal(false);
    }

    /**
     * Re-enable protection after populateRecord.
     */
    protected function afterHazeltreePopulateRecord(): void
    {
        $this->protectHazeltreeInternal(true);
    }

    /**
     * Disable protection before refreshInternal.
     */
    protected function beforeHazeltreeRefreshInternal(): void
    {
        $this->protectHazeltreeInternal(false);
    }

    /**
     * Re-enable protection after refreshInternal.
     */
    protected function afterHazeltreeRefreshInternal(): void
    {
        $this->protectHazeltreeInternal(true);
    }

    /**
     * Delete this node and all its descendants, then close the gap.
     *
     * @return int The number of rows deleted
     */
    protected function hazeltreeDeleteInternal(): int
    {
        $nextSibling = $this->relativeQuery()->siblings()->next()->one();
        $nextSiblingId = $nextSibling !== null ? $this->getPkValue($nextSibling) : null;

        $db = $this->db();
        $transaction = $db->beginTransaction();

        try {
            $condition = [
                'and',
                [
                    '>=',
                    $this->leftColumn(),
                    $this->left,
                ],
                [
                    '<=',
                    $this->rightColumn(),
                    $this->right,
                ],
            ];

            $deletedCount = $this->deleteAll($condition);

            $this->moveBackItems($nextSiblingId);

            $this->assignOldValues();

            $transaction->commit();

            return $deletedCount;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Internal method to toggle system protection.
     */
    private function protectHazeltreeInternal(bool $protect): void
    {
        $this->hazelTreeSystemProtected = $protect;
    }

    private function getNextRootPath(): string
    {
        $lastRoot = static::query()
            ->roots()
            ->previous()
            ->one();

        $nextRootPath = '1';
        if ($lastRoot !== null) {
            $lastSegment = TreeHelper::getLastSegment($lastRoot->path);
            $nextRootPath = (string) ($lastSegment + 1);
        }

        return $nextRootPath;
    }

    private function setPathInternal(?string $value): void
    {
        $this->path = $value;
        $this->nodeMatrix = null;
    }

    /**
     * Check if this is a root node (level 1).
     */
    public function isRoot(): bool
    {
        return $this->level === 1;
    }

    /**
     * Check if this node can be moved to a target path: the target is neither this node nor one of its descendants.
     */
    public function canMove(string $targetPath): bool
    {
        $isSelfOrDescendant = $targetPath === $this->path
            || str_starts_with($targetPath, $this->path . TreeHelper::PATH_SEPARATOR) === true;
        return $isSelfOrDescendant === false;
    }

    /**
     * Get a query builder for tree navigation relative to this node.
     */
    public function relativeQuery(): \Yiisoft\ActiveRecord\ActiveQueryInterface
    {
        $query = static::query();

        if (method_exists($query, 'forNode') === true) {
            $query->forNode($this);
        }

        return $query;
    }

    private function setNodePath(string $nodePath): void
    {
        $this->nodeMatrix = TreeHelper::convert($nodePath);
        $this->path = $nodePath;
        $this->level = TreeHelper::getLevel($nodePath);
        $this->left = TreeHelper::getLeft($this->nodeMatrix);
        $this->right = TreeHelper::getRight($this->nodeMatrix);
    }

    private function setNodeMatrix(Matrix $matrix): void
    {
        $this->nodeMatrix = $matrix;
        $this->path = TreeHelper::convert($matrix);
        $this->level = TreeHelper::getLevel($this->path);
        $this->left = TreeHelper::getLeft($this->nodeMatrix);
        $this->right = TreeHelper::getRight($this->nodeMatrix);
    }

    private function getNodeMatrix(): Matrix
    {
        if ($this->nodeMatrix === null && $this->path !== null) {
            $this->nodeMatrix = TreeHelper::convert($this->path);
        }
        return $this->nodeMatrix ?? TreeHelper::convert('1');
    }

    private function itemForTarget(HazeltreeInterface|string $target): HazeltreeInterface
    {
        if ($target instanceof HazeltreeInterface) {
            $item = $target;
        } else {
            $item = static::query()
                ->andWhere([$this->pathColumn() => $target])
                ->one();

            if ($item === null) {
                throw new InvalidItemConfigurationException(sprintf('Node with path "%s" not found', $target));
            }
        }

        return $item;
    }

    /**
     * Insert or save current item into target item at last position.
     *
     * @throws InvalidItemConfigurationException
     */
    public function saveInto(HazeltreeInterface|string $target, ?array $attributeNames = null): bool
    {
        $targetItem = $this->itemForTarget($target);
        if ($this->isNew() === false) {
            $db = $this->db();
            $transaction = $db->beginTransaction();
            try {
                $this->save($attributeNames);

                if ($this->canMove($targetItem->path) === true) {
                    $currentParent = $this->relativeQuery()->parent()->one();

                    $nextSibling = $this->relativeQuery()->siblings()->next()->one();
                    $bumpBackMatrix = null;
                    if ($nextSibling !== null) {
                        $bumpBackMatrix = $this->prepareMoveMatrix($nextSibling, $nextSibling, -1);
                    }

                    $pk = static::primaryKey();
                    $pkName = $pk[0];
                    $thisId = $this->get($pkName);

                    $targetLastChild = $targetItem->relativeQuery()->children()
                        ->andWhere([
                            '<>',
                            $pkName,
                            $thisId,
                        ])
                        ->reverse()
                        ->one();

                    $currentLastSegment = TreeHelper::getLastSegment($this->getNodeMatrix());
                    if ($targetLastChild !== null) {
                        $lastSegment = TreeHelper::getLastSegment($targetLastChild->getNodeMatrix());
                        $bump = ($lastSegment + 1) - $currentLastSegment;
                        $nodesMoveMatrix = $this->prepareMoveMatrix($this, $targetLastChild, $bump);
                    } else {
                        $bump = 1 - $currentLastSegment;
                        $nodesMoveMatrix = $this->prepareMoveMatrix($this, $targetItem, $bump, true);
                    }

                    $nodesToMove = $this->relativeQuery()->children()
                        ->includeDescendants()
                        ->includeSelf();
                    /** @var static $nodeToMove */
                    foreach ($nodesToMove->each() as $nodeToMove) {
                        $childMatrix = $nodeToMove->getNodeMatrix();
                        $newMatrix = $nodesMoveMatrix->multiply($childMatrix);
                        $nodeToMove->setNodeMatrix($newMatrix);
                        $nodeToMove->save([
                            $this->pathColumn(),
                            $this->leftColumn(),
                            $this->rightColumn(),
                            $this->levelColumn(),
                        ]);
                    }

                    if ($bumpBackMatrix !== null && $nextSibling !== null) {
                        $nodesToBumpBackQuery = $nextSibling->relativeQuery()
                            ->siblings()
                            ->next()
                            ->includeSelf()
                            ->includeDescendants();
                        /** @var static $nodeToBumpBack */
                        foreach ($nodesToBumpBackQuery->each() as $nodeToBumpBack) {
                            $childMatrix = $nodeToBumpBack->getNodeMatrix();
                            $newMatrix = $bumpBackMatrix->multiply($childMatrix);
                            $nodeToBumpBack->setNodeMatrix($newMatrix);
                            $nodeToBumpBack->save([
                                $this->pathColumn(),
                                $this->leftColumn(),
                                $this->rightColumn(),
                                $this->levelColumn(),
                            ]);
                        }
                    }
                    $this->refresh();
                }

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } elseif ($this->path !== null) {
            throw new InvalidItemConfigurationException('Cannot "saveInto()" a new record with a node path');
        } else {
            $db = $this->db();
            $transaction = $db->beginTransaction();
            try {
                $lastChild = $targetItem->relativeQuery()->children()
                    ->reverse()
                    ->one();

                if ($lastChild === null) {
                    $lastSegment = 1;
                } else {
                    $lastSegment = TreeHelper::getLastSegment($lastChild->getNodeMatrix()) + 1;
                }

                $this->setNodePath($targetItem->path . TreeHelper::PATH_SEPARATOR . $lastSegment);
                $this->save($attributeNames);

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return true;
    }

    /**
     * Insert or save current item before target item.
     *
     * @throws InvalidItemConfigurationException
     */
    public function saveBefore(HazeltreeInterface|string $target, ?array $attributeNames = null): bool
    {
        $targetItem = $this->itemForTarget($target);

        if ($this->isNew() === false) {
            $db = $this->db();
            $transaction = $db->beginTransaction();
            try {
                $this->save($attributeNames);

                if ($this->canMove($targetItem->path) === true) {
                    $nextSibling = $this->relativeQuery()->siblings()->next()->one();
                    $nextSiblingId = $nextSibling !== null ? $this->getPkValue($nextSibling) : null;

                    $bumpMatrix = $this->prepareMoveMatrix($targetItem, $targetItem, 1);
                    $nodesToBump = $targetItem->relativeQuery()->siblings()->next()->includeSelf()->includeDescendants()
                        ->reverse();
                    $this->moveAndSaveItems($nodesToBump, $bumpMatrix);

                    $targetItem->refresh();
                    $this->refresh();

                    $this->moveThisItemTree($targetItem, true);

                    $targetItem->refresh();
                    $this->refresh();

                    $this->moveBackItems($nextSiblingId);

                    $targetItem->refresh();
                    $this->refresh();
                }

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } elseif ($this->path !== null) {
            throw new InvalidItemConfigurationException('Cannot "saveBefore()" a new record with a node path');
        } else {
            $db = $this->db();
            $transaction = $db->beginTransaction();
            try {
                $path = $targetItem->path;

                $nodesMoveMatrix = $this->prepareMoveMatrix($targetItem, $targetItem, 1);

                $nodesToMove = $targetItem->relativeQuery()->siblings()->next()->includeSelf()->includeDescendants()
                    ->reverse();

                $this->moveAndSaveItems($nodesToMove, $nodesMoveMatrix);

                $this->setNodePath($path);
                $this->save($attributeNames);

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return true;
    }

    /**
     * Insert or save current item after target item.
     *
     * @throws InvalidItemConfigurationException
     */
    public function saveAfter(HazeltreeInterface|string $target, ?array $attributeNames = null): bool
    {
        $targetItem = $this->itemForTarget($target);

        if ($this->isNew() === false) {
            $db = $this->db();
            $transaction = $db->beginTransaction();
            try {
                $this->save($attributeNames);

                if ($this->canMove($targetItem->path) === true) {
                    $targetItemNextSibling = $targetItem->relativeQuery()->siblings()->next()->one();

                    if ($targetItemNextSibling !== null) {
                        $nextSibling = $this->relativeQuery()->siblings()->next()->one();
                        $nextSiblingId = $nextSibling !== null ? $this->getPkValue($nextSibling) : null;

                        $bumpMatrix = $this->prepareMoveMatrix($targetItemNextSibling, $targetItemNextSibling, 1);
                        $nodesToBump = $targetItemNextSibling->relativeQuery()->siblings()->next()->includeSelf()->includeDescendants()
                            ->reverse();
                        $this->moveAndSaveItems($nodesToBump, $bumpMatrix);

                        $targetItemNextSibling->refresh();
                        $this->refresh();

                        $this->moveThisItemTree($targetItemNextSibling, true);

                        $targetItemNextSibling->refresh();
                        $this->refresh();

                        $this->moveBackItems($nextSiblingId);

                        $targetItemNextSibling->refresh();
                        $this->refresh();
                    } else {
                        $nextSibling = $this->relativeQuery()->siblings()->next()->one();
                        $nextSiblingId = $nextSibling !== null ? $this->getPkValue($nextSibling) : null;

                        $this->moveThisItemTree($targetItem, false);

                        $this->refresh();

                        $this->moveBackItems($nextSiblingId);

                        $targetItem->refresh();
                        $this->refresh();
                    }
                }

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        } elseif ($this->path !== null) {
            throw new InvalidItemConfigurationException('Cannot "saveAfter()" a new record with a node path');
        } else {
            $db = $this->db();
            $transaction = $db->beginTransaction();
            try {
                $nextSiblingItem = $targetItem->relativeQuery()->siblings()->next()->one();

                if ($nextSiblingItem !== null) {
                    $path = $nextSiblingItem->path;

                    $nodesMoveMatrix = $this->prepareMoveMatrix($nextSiblingItem, $nextSiblingItem, 1);

                    $nodesToMove = $targetItem->relativeQuery()->siblings()->next()->includeDescendants()
                        ->reverse();

                    $this->moveAndSaveItems($nodesToMove, $nodesMoveMatrix);
                } else {
                    $parts = explode(TreeHelper::PATH_SEPARATOR, $targetItem->path);
                    $lastSegment = (int) array_pop($parts);
                    $parts[] = (string) ($lastSegment + 1);
                    $path = implode(TreeHelper::PATH_SEPARATOR, $parts);
                }

                $this->setNodePath($path);
                $this->save($attributeNames);

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return true;
    }

    public function getSubtreeDepth(): int
    {
        $maxLevel = $this->level;
        $descendants = $this->relativeQuery()->children()->includeDescendants();
        /** @var static $descendant */
        foreach ($descendants->each() as $descendant) {
            if ($descendant->getLevel() > $maxLevel) {
                $maxLevel = $descendant->getLevel();
            }
        }
        return $maxLevel - $this->level;
    }

    public function getMaxLevelIfMoveBefore(HazeltreeInterface $target): int
    {
        $newLevel = $target->getLevel();
        return $newLevel + $this->getSubtreeDepth();
    }

    public function getMaxLevelIfMoveAfter(HazeltreeInterface $target): int
    {
        $newLevel = $target->getLevel();
        return $newLevel + $this->getSubtreeDepth();
    }

    public function getMaxLevelIfMoveInto(HazeltreeInterface $target): int
    {
        $newLevel = $target->getLevel() + 1;
        return $newLevel + $this->getSubtreeDepth();
    }

    public function wouldExceedMaxLevel(HazeltreeInterface $target, string $mode, int $maxLevel): bool
    {
        $resultLevel = match ($mode) {
            'before' => $this->getMaxLevelIfMoveBefore($target),
            'after' => $this->getMaxLevelIfMoveAfter($target),
            'into' => $this->getMaxLevelIfMoveInto($target),
            default => throw new \InvalidArgumentException('Invalid move mode: ' . $mode),
        };
        return $resultLevel > $maxLevel;
    }

    private function moveThisItemTree(HazeltreeInterface $targetItem, bool $moveBefore): void
    {
        $itemLastSegment = TreeHelper::getLastSegment($this->getNodeMatrix());
        $targetLastSegment = TreeHelper::getLastSegment($targetItem->getNodeMatrix());

        if ($moveBefore) {
            $itemBump = $targetLastSegment - $itemLastSegment - 1;
        } else {
            $itemBump = $targetLastSegment - $itemLastSegment + 1;
        }

        $nodesMoveMatrix = $this->prepareMoveMatrix($this, $targetItem, $itemBump);
        $nodesToMove = $this->relativeQuery()->children()->includeDescendants()->includeSelf();
        $this->moveAndSaveItems($nodesToMove, $nodesMoveMatrix);
    }

    private function getPkValue(HazeltreeInterface $item): mixed
    {
        $pk = static::primaryKey();
        $pkName = $pk[0];
        return $item->get($pkName);
    }

    private function moveBackItems(mixed $nextSiblingId): void
    {
        if ($nextSiblingId !== null) {
            $pk = static::primaryKey();
            $pkName = $pk[0];

            $nextSibling = static::query()
                ->andWhere([$pkName => $nextSiblingId])
                ->one();

            if ($nextSibling !== null) {
                $bumpBackMatrix = $this->prepareMoveMatrix($nextSibling, $nextSibling, -1);
                $nodesToMove = $nextSibling->relativeQuery()->siblings()->next()->includeSelf()->includeDescendants()
                    ->natural();
                $this->moveAndSaveItems($nodesToMove, $bumpBackMatrix);
            }
        }
    }

    private function moveAndSaveItems(\Yiisoft\ActiveRecord\ActiveQueryInterface $itemsToMove, Matrix $moveMatrix): void
    {
        /** @var static $itemToMove */
        foreach ($itemsToMove->each() as $itemToMove) {
            $childMatrix = $itemToMove->getNodeMatrix();
            $itemMoveMatrix = $moveMatrix->multiply($childMatrix);
            $itemToMove->setNodeMatrix($itemMoveMatrix);
            $itemToMove->save([
                $this->pathColumn(),
                $this->leftColumn(),
                $this->rightColumn(),
                $this->levelColumn(),
            ]);
        }
    }

    private function prepareMoveMatrix(HazeltreeInterface $fromItem, HazeltreeInterface $toItem, int $bump, bool $inside = false): Matrix
    {
        $fromMatrix = TreeHelper::getParent($fromItem->getNodeMatrix()) ?? TreeHelper::getRootMatrix();
        if ($inside) {
            $toMatrix = $toItem->getNodeMatrix();
        } else {
            $toMatrix = TreeHelper::getParent($toItem->getNodeMatrix()) ?? TreeHelper::getRootMatrix();
        }
        return TreeHelper::buildMoveMatrix($fromMatrix, $toMatrix, $bump);
    }
}
