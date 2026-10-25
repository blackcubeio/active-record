<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Auto-qualify column names with table/alias prefix in where conditions.
 *
 * Prevents ambiguous column errors in joins by prefixing simple column names
 * with the table qualifier. Transparent to callers — scopes write simple
 * column names, qualification happens underneath.
 *
 * Requires: used on an ActiveQuery subclass (needs getFrom(), getModel(), parent::where()).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait QualifyColumnTrait
{
    private ?string $tableName = null;
    private ?string $qualifier = null;

    protected function getQualifier(): string
    {
        if ($this->qualifier === null) {
            $from = $this->getFrom();
            if (empty($from) === false && is_string(array_key_first($from)) === true) {
                $this->qualifier = (string) array_key_first($from);
            } else {
                $this->qualifier = $this->tableName;
            }
        }
        return $this->qualifier;
    }

    public function alias(string $alias): static
    {
        $this->qualifier = null;
        return parent::alias($alias);
    }

    private function qualifyCondition(mixed $condition): mixed
    {
        $qualified = $condition;
        if (is_array($condition) === true) {
            $this->ensureTableName();
            $qualifier = $this->getQualifier();

            if (isset($condition[0]) === false) {
                $qualified = [];
                foreach ($condition as $column => $value) {
                    if (is_string($column) === true && $this->isSimpleColumn($column) === true) {
                        $column = $qualifier . '.[[' . $column . ']]';
                    }
                    $qualified[$column] = $value;
                }
            } else {
                $operator = $condition[0];

                if (is_string($operator) === true && in_array(strtolower($operator), [
                    'and',
                    'or',
                    'not',
                ], true) === true) {
                    for ($i = 1, $count = count($condition); $i < $count; $i++) {
                        $qualified[$i] = $this->qualifyCondition($condition[$i]);
                    }
                } elseif (is_string($operator) === true && isset($condition[1]) === true && is_string($condition[1]) === true && $this->isSimpleColumn($condition[1]) === true) {
                    $qualified[1] = $qualifier . '.[[' . $condition[1] . ']]';
                }
            }
        }

        return $qualified;
    }

    private function isSimpleColumn(string $column): bool
    {
        return str_contains($column, '.') === false && str_contains($column, '(') === false && str_contains($column, '[[') === false;
    }

    public function where(array|string|ExpressionInterface|null $condition, array $params = []): static
    {
        return parent::where($this->qualifyCondition($condition), $params);
    }

    public function andWhere($condition, array $params = []): static
    {
        return parent::andWhere($this->qualifyCondition($condition), $params);
    }

    public function orWhere(array|string|ExpressionInterface $condition, array $params = []): static
    {
        return parent::orWhere($this->qualifyCondition($condition), $params);
    }

    protected function ensureTableName(): void
    {
        if ($this->tableName === null) {
            $this->tableName = $this->getModel()->tableName();
        }
    }
}
