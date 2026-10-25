<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Elastic;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

use function array_keys;
use function in_array;
use function is_array;
use function is_int;
use function preg_match;
use function strtoupper;

/**
 * Base trait providing elastic JSON virtual column support for ActiveQuery classes.
 *
 * Automatically converts references to elastic (virtual) columns
 * in WHERE and ORDER BY clauses to JSON_VALUE() expressions.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
trait BaseElasticQueryTrait
{
    /** @var array<string, array<string>> Cache of real attributes per model class */
    private static array $realAttributesCache = [];

    /**
     * Clear the real attributes cache. Useful for tests when tables are recreated.
     */
    public static function clearRealAttributesCache(): void
    {
        self::$realAttributesCache = [];
    }

    /** @var list<string> Operators that take column as second element */
    private const COLUMN_OPERATORS = [
        'BETWEEN',
        'NOT BETWEEN',
        'IN',
        'NOT IN',
        'LIKE',
        'NOT LIKE',
        'OR LIKE',
        'OR NOT LIKE',
        '>',
        '<',
        '>=',
        '<=',
        '=',
        '!=',
        '<>',
    ];

    /** @var list<string> Logical operators with nested conditions */
    private const LOGICAL_OPERATORS = [
        'AND',
        'OR',
        'NOT',
    ];

    private function getJsonColumn(): string
    {
        $model = $this->getModel();
        return $model->elasticColumn();
    }

    /**
     * @return array<string>
     */
    public function getRealAttributes(): array
    {
        $model = $this->getModel();
        $modelClass = $model::class;

        if (isset(self::$realAttributesCache[$modelClass]) === false) {
            $tableName = $model->tableName();
            $schema = $this->db->getTableSchema($tableName);
            self::$realAttributesCache[$modelClass] = $schema !== null ? array_keys($schema->getColumns()) : [];
        }

        return self::$realAttributesCache[$modelClass];
    }

    public function where(array|string|ExpressionInterface|null $condition, array $params = []): static
    {
        if (is_array($condition) === true) {
            $condition = $this->buildVirtualCondition($condition);
        }
        return parent::where($condition, $params);
    }

    public function setWhere(array|string|ExpressionInterface|null $condition, array $params = []): static
    {
        if (is_array($condition) === true) {
            $condition = $this->buildVirtualCondition($condition);
        }
        return parent::setWhere($condition, $params);
    }

    public function andWhere($condition, array $params = []): static
    {
        if (is_array($condition) === true) {
            $condition = $this->buildVirtualCondition($condition);
        }
        return parent::andWhere($condition, $params);
    }

    public function orWhere(array|string|ExpressionInterface $condition, array $params = []): static
    {
        if (is_array($condition) === true) {
            $condition = $this->buildVirtualCondition($condition);
        }
        return parent::orWhere($condition, $params);
    }

    public function orderBy(array|string|ExpressionInterface $columns): static
    {
        if (is_array($columns) === true) {
            $columns = $this->buildOrderBy($columns);
        }
        return parent::orderBy($columns);
    }

    public function addOrderBy(array|string|ExpressionInterface $columns): static
    {
        if (is_array($columns) === true) {
            $columns = $this->buildOrderBy($columns);
        }
        return parent::addOrderBy($columns);
    }

    private function buildOrderBy(array $columns): array
    {
        $result = [];
        foreach ($columns as $column => $direction) {
            $newColumn = $this->buildVirtualColumn($column);
            if ($newColumn instanceof Expression) {
                $result[(string) $newColumn] = $direction;
            } else {
                $result[$newColumn] = $direction;
            }
        }
        return $result;
    }

    private function isVirtualColumn(mixed $column): bool
    {
        $isVirtual = false;
        if (is_string($column) === true && is_int($column) === false) {
            $extractedColumn = $column;
            $isOwnTable = true;
            $model = $this->getModel();
            $modelTable = $model->tableName();

            if (preg_match('/^\s*([^.]+)\.([\w_-]+)\s*$/', $extractedColumn, $matches) === 1) {
                $table = $matches[1];
                $extractedColumn = $matches[2];
                $isOwnTable = $table === $modelTable || $table === '{{%' . trim($modelTable, '{}%') . '}}';
            }

            if ($isOwnTable === true && preg_match('/\s*([\w_-]+)\s*$/', $extractedColumn, $matches) === 1) {
                $columnToCheck = $matches[1];
                $isVirtual = in_array($columnToCheck, $this->getRealAttributes(), true) === false;
            }
        }

        return $isVirtual;
    }

    private function getVirtualColumn(string $column): string
    {
        $virtualColumn = $column;
        if (preg_match('/^\s*[^.]+\.([\w_-]+)\s*$/', $column, $matches) === 1) {
            $virtualColumn = $matches[1];
        }

        return $virtualColumn;
    }

    private function buildVirtualColumn(mixed $column): mixed
    {
        $built = $column;
        if ($this->isVirtualColumn($column) === true) {
            $virtualColumn = $this->getVirtualColumn($column);
            $this->ensureTableName();
            $qualifier = $this->getQualifier();

            $built = new Expression(
                'JSON_VALUE(' . $qualifier . '.[[' . $this->getJsonColumn() . ']], \'$.' . $virtualColumn . '\')'
            );
        }

        return $built;
    }

    private function isVirtualCondition(array $condition): bool
    {
        $isVirtual = false;
        if (isset($condition[0]) === true) {
            $operator = strtoupper((string) $condition[0]);

            if (in_array($operator, self::COLUMN_OPERATORS, true) === true) {
                if (isset($condition[1]) === true && $this->isVirtualColumn($condition[1]) === true) {
                    $isVirtual = true;
                }
            } elseif (in_array($operator, self::LOGICAL_OPERATORS, true) === true) {
                $conditionCount = count($condition);
                for ($i = 1; $i < $conditionCount; $i++) {
                    if ($isVirtual === false && is_array($condition[$i]) === true && $this->isVirtualCondition($condition[$i]) === true) {
                        $isVirtual = true;
                    }
                }
            }
        }

        if ($isVirtual === false) {
            foreach ($condition as $column => $value) {
                if ($isVirtual === false && is_int($column) === false && $this->isVirtualColumn($column) === true) {
                    $isVirtual = true;
                }
            }
        }

        return $isVirtual;
    }

    private function buildVirtualCondition(array $condition): array
    {
        $built = $condition;
        if ($this->isVirtualCondition($condition) === true) {
            if (isset($condition[0]) === true) {
                $operator = strtoupper((string) $condition[0]);

                if (in_array($operator, self::COLUMN_OPERATORS, true) === true) {
                    if (isset($condition[1]) === true && $this->isVirtualColumn($condition[1]) === true) {
                        $built[1] = $this->buildVirtualColumn($condition[1]);
                    }
                } elseif (in_array($operator, self::LOGICAL_OPERATORS, true) === true) {
                    $conditionCount = count($condition);
                    for ($i = 1; $i < $conditionCount; $i++) {
                        if (is_array($condition[$i]) === true) {
                            $built[$i] = $this->buildVirtualCondition($condition[$i]);
                        }
                    }
                }
            } else {
                $built = [];
                foreach ($condition as $column => $value) {
                    if (is_int($column) === false && $this->isVirtualColumn($column) === true) {
                        $built[(string) $this->buildVirtualColumn($column)] = $value;
                    } else {
                        $built[$column] = $value;
                    }
                }
            }
        }

        return $built;
    }
}
