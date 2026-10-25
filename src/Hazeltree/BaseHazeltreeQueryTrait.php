<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Hazeltree;

use Blackcube\ActiveRecord\Hazeltree\Helpers\TreeHelper;

/**
 * Base trait providing composable tree navigation methods for ActiveQuery.
 *
 * This trait should be used on an ActiveQuery class that queries models using BaseHazeltreeTrait.
 * It provides a fluent API with scopes and modifiers.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BaseHazeltreeQueryTrait
{
    /**
     * The node to query relative to.
     */
    private ?HazeltreeInterface $hazelTreeNode = null;

    /**
     * Whether a scope requiring node context has been applied.
     */
    private bool $scoped = false;

    /**
     * Current scope: 'children', 'parent', 'siblings', or null.
     */
    private ?string $hazelTreeScope = null;

    /**
     * Direction modifier for siblings: 'next', 'previous', or null (all).
     */
    private ?string $hazelTreeDirection = null;

    /**
     * Whether to include descendants of matched nodes.
     */
    private bool $hazelTreeIncludeDescendants = false;

    /**
     * Whether to include ancestors (for parent scope).
     */
    private bool $hazelTreeIncludeAncestors = false;

    /**
     * Whether to include the reference node itself.
     */
    private bool $hazelTreeIncludeSelf = false;

    /**
     * Whether to reverse the default sort order (DESC instead of ASC).
     */
    private bool $hazelTreeReverse = false;

    /**
     * Whether to exclude the reference node itself.
     */
    private bool $hazelTreeExcludeSelf = false;

    /**
     * Whether to exclude descendants of the reference node.
     */
    private bool $hazelTreeExcludeDescendants = false;

    public function forNode(HazeltreeInterface $node): static
    {
        $this->hazelTreeNode = $node;
        return $this;
    }

    public function roots(): static
    {
        $this->scoped = true;
        $this->hazelTreeScope = 'roots';
        return $this;
    }

    public function children(): static
    {
        $this->scoped = true;
        $this->hazelTreeScope = 'children';
        return $this;
    }

    public function parent(): static
    {
        $this->scoped = true;
        $this->hazelTreeScope = 'parent';
        return $this;
    }

    public function siblings(): static
    {
        $this->scoped = true;
        $this->hazelTreeScope = 'siblings';
        return $this;
    }

    public function excludingSelf(): static
    {
        $this->scoped = true;
        $this->hazelTreeScope = 'excluding';
        $this->hazelTreeExcludeSelf = true;
        return $this;
    }

    public function excludingDescendants(): static
    {
        $this->scoped = true;
        $this->hazelTreeScope = 'excluding';
        $this->hazelTreeExcludeDescendants = true;
        return $this;
    }

    public function next(): static
    {
        $this->hazelTreeDirection = 'next';
        return $this;
    }

    public function previous(): static
    {
        $this->hazelTreeDirection = 'previous';
        return $this;
    }

    public function includeDescendants(): static
    {
        $this->hazelTreeIncludeDescendants = true;
        return $this;
    }

    public function includeAncestors(): static
    {
        $this->hazelTreeIncludeAncestors = true;
        return $this;
    }

    public function includeSelf(): static
    {
        $this->hazelTreeIncludeSelf = true;
        return $this;
    }

    public function natural(): static
    {
        $this->hazelTreeReverse = false;
        return $this;
    }

    public function reverse(): static
    {
        $this->hazelTreeReverse = true;
        return $this;
    }

    public function prepare($builder): \Yiisoft\Db\Query\QueryInterface
    {
        if ($this->supportsHazeltree() === true) {
            if ($this->scoped === true) {
                $this->applyTreeConditions();
            } else {
                $this->applyOrderByOnly();
            }
        }

        return parent::prepare($builder);
    }

    private function supportsHazeltree(): bool
    {
        $model = $this->hazelTreeNode ?? $this->getModel();
        return $model instanceof HazeltreeInterface;
    }

    /**
     * Qualifies a tree column with the query alias when one is set, with the table name otherwise.
     */
    private function qualifyColumn(string $column): string
    {
        $from = $this->getFrom();
        if (empty($from) === false && is_string(array_key_first($from)) === true) {
            $qualifier = (string) array_key_first($from);
        } else {
            $model = $this->hazelTreeNode ?? $this->getModel();
            $qualifier = $model->tableName();
        }
        return $qualifier . '.[[' . $column . ']]';
    }

    private function applyOrderByOnly(): void
    {
        if ($this->getOrderBy() === []) {
            $model = $this->hazelTreeNode ?? $this->getModel();
            $this->orderBy([$this->qualifyColumn($model->leftColumn()) => $this->getDefaultSortDirection()]);
        }
    }

    private function applyTreeConditions(): void
    {
        /** @var HazeltreeInterface $model */
        $model = $this->hazelTreeNode ?? $this->getModel();
        $leftCol = $this->qualifyColumn($model->leftColumn());

        if ($this->hazelTreeScope === 'roots') {
            $this->applyRootsConditions($model);
        } elseif ($this->hazelTreeNode !== null) {
            $node = $this->hazelTreeNode;
            $rightCol = $this->qualifyColumn($node->rightColumn());
            $levelCol = $this->qualifyColumn($node->levelColumn());

            match ($this->hazelTreeScope) {
                'children' => $this->applyChildrenConditions($node, $leftCol, $rightCol, $levelCol),
                'parent' => $this->applyParentConditions($node, $leftCol, $rightCol, $levelCol),
                'siblings' => $this->applySiblingsConditions($node, $leftCol, $rightCol, $levelCol),
                'excluding' => $this->applyExcludingConditions($node, $leftCol, $rightCol),
                default => null,
            };
        }

        if ($this->getOrderBy() === []) {
            $this->orderBy([$leftCol => $this->getDefaultSortDirection()]);
        }
    }

    private function applyRootsConditions(HazeltreeInterface $model): void
    {
        $this->andWhere([$this->qualifyColumn($model->levelColumn()) => 1]);
    }

    private function applyChildrenConditions(
        HazeltreeInterface $node,
        string $leftCol,
        string $rightCol,
        string $levelCol
    ): void {
        $leftOp = $this->hazelTreeIncludeSelf ? '>=' : '>';
        $rightOp = $this->hazelTreeIncludeSelf ? '<=' : '<';

        $this->andWhere([
            $leftOp,
            $leftCol,
            $node->left,
        ]);
        $this->andWhere([
            $rightOp,
            $rightCol,
            $node->right,
        ]);

        if ($this->hazelTreeIncludeDescendants === false) {
            $this->andWhere([$levelCol => $node->level + 1]);
        }
    }

    private function applyParentConditions(
        HazeltreeInterface $node,
        string $leftCol,
        string $rightCol,
        string $levelCol
    ): void {
        $leftOp = $this->hazelTreeIncludeSelf ? '<=' : '<';
        $rightOp = $this->hazelTreeIncludeSelf ? '>=' : '>';

        $this->andWhere([
            $leftOp,
            $leftCol,
            $node->left,
        ]);
        $this->andWhere([
            $rightOp,
            $rightCol,
            $node->right,
        ]);

        if ($this->hazelTreeIncludeAncestors === false) {
            $this->andWhere([$levelCol => $node->level - 1]);
        }
    }

    private function applySiblingsConditions(
        HazeltreeInterface $node,
        string $leftCol,
        string $rightCol,
        string $levelCol
    ): void {
        if ($node->isRoot() === true) {
            $this->applyRootSiblingsConditions($node, $leftCol, $rightCol, $levelCol);
        } else {
            $parentMatrix = TreeHelper::getParent(TreeHelper::convert($node->path));
            $parentLeft = TreeHelper::getLeft($parentMatrix);
            $parentRight = TreeHelper::getRight($parentMatrix);

            $this->andWhere([
                '>',
                $leftCol,
                $parentLeft,
            ]);
            $this->andWhere([
                '<',
                $rightCol,
                $parentRight,
            ]);

            if ($this->hazelTreeDirection === 'next') {
                $this->andWhere([
                    '>=',
                    $leftCol,
                    $this->hazelTreeIncludeSelf ? $node->left : $node->right,
                ]);
            } elseif ($this->hazelTreeDirection === 'previous') {
                $this->andWhere([
                    '<=',
                    $rightCol,
                    $this->hazelTreeIncludeSelf ? $node->right : $node->left,
                ]);
            } elseif ($this->hazelTreeIncludeSelf === false) {
                $this->andWhere([
                    '<>',
                    $leftCol,
                    $node->left,
                ]);
            }

            if ($this->hazelTreeIncludeDescendants === false) {
                $this->andWhere([$levelCol => $node->level]);
            }
        }
    }

    private function applyRootSiblingsConditions(
        HazeltreeInterface $node,
        string $leftCol,
        string $rightCol,
        string $levelCol
    ): void {
        if ($this->hazelTreeDirection === 'next') {
            $this->andWhere([
                '>=',
                $leftCol,
                $this->hazelTreeIncludeSelf ? $node->left : $node->right,
            ]);
        } elseif ($this->hazelTreeDirection === 'previous') {
            $this->andWhere([
                '<=',
                $rightCol,
                $this->hazelTreeIncludeSelf ? $node->right : $node->left,
            ]);
        } elseif ($this->hazelTreeIncludeSelf === false) {
            $this->andWhere([
                '<>',
                $leftCol,
                $node->left,
            ]);
        }

        if ($this->hazelTreeIncludeDescendants === false) {
            $this->andWhere([$levelCol => 1]);
        }
    }

    private function applyExcludingConditions(
        HazeltreeInterface $node,
        string $leftCol,
        string $rightCol
    ): void {
        if ($this->hazelTreeExcludeSelf === true && $this->hazelTreeExcludeDescendants === true) {
            $this->andWhere([
                'not',
                [
                    'and',
                    [
                        '>=',
                        $leftCol,
                        $node->left,
                    ],
                    [
                        '<=',
                        $rightCol,
                        $node->right,
                    ],
                ],
            ]);
        } elseif ($this->hazelTreeExcludeSelf === true) {
            $this->andWhere([
                '<>',
                $leftCol,
                $node->left,
            ]);
        } elseif ($this->hazelTreeExcludeDescendants === true) {
            $this->andWhere([
                'not',
                [
                    'and',
                    [
                        '>',
                        $leftCol,
                        $node->left,
                    ],
                    [
                        '<',
                        $rightCol,
                        $node->right,
                    ],
                ],
            ]);
        }
    }

    private function getDefaultSortDirection(): int
    {
        $isDescByDefault = $this->hazelTreeDirection === 'previous';

        if ($this->hazelTreeReverse === true) {
            $direction = $isDescByDefault === true ? SORT_ASC : SORT_DESC;
        } else {
            $direction = $isDescByDefault === true ? SORT_DESC : SORT_ASC;
        }

        return $direction;
    }
}
