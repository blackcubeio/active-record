# active-record — Hazeltree (nested sets)

## Columns

| Column | Type | Override |
|---|---|---|
| `path` | VARCHAR, unique, dot notation (`1.2.3`) | `pathColumn()` |
| `left` | DECIMAL/DOUBLE | `leftColumn()` |
| `right` | DECIMAL/DOUBLE | `rightColumn()` |
| `level` | INT, `1` = root | `levelColumn()` |

The four values are computed from the path (Dan Hazel's rational numbers, `TreeHelper`). They are read-only from outside: setting one → `Error` `Cannot set read-only tree property <Class>::$path. Use saveInto(), saveBefore() or saveAfter(), or protectHazeltree(false).`; `protectHazeltree(false)` lifts the protection.

## Writing (model with `HazeltreeTrait` / `HazeltreeElasticTrait`)

| Operation | Behaviour |
|---|---|
| `save()` on a new node without path | next root (`1`, `2`, …: last root + 1) |
| `saveInto(HazeltreeInterface\|string $target, ?array $attributeNames = null)` | new node: last child of the target; existing node: moved with its subtree as last child |
| `saveBefore($target, …)` / `saveAfter($target, …)` | new node inserted / existing node moved before / after the target; following siblings shifted |
| target as string | node looked up by path; missing → `InvalidItemConfigurationException` `Node with path "<path>" not found` |
| new node that already has a path | `InvalidItemConfigurationException` `Cannot "saveInto()" a new record with a node path` (same for before/after) |
| move into itself or its own subtree | `canMove()` is `false`: the node is saved, not moved; the method still returns `true` |
| `delete()` | node + descendants deleted, following siblings shifted back; returns the row count |

Writes run in a transaction; moves rewrite `path`/`left`/`right`/`level` of the moved subtree and of the shifted siblings.

`canMove(string $targetPath)`: `false` when the target is the node or one of its descendants (`1.1` → `1.10` is `true`, `1.2` → `1.2.1` is `false`).

Level helpers: `getSubtreeDepth()`, `getMaxLevelIfMoveBefore/After/Into($target)`, `wouldExceedMaxLevel($target, 'before'|'after'|'into', int $maxLevel)` (other mode → `InvalidArgumentException`). `isRoot()` = level 1.

## Reading (query with `BaseHazeltreeQueryTrait`)

`$node->relativeQuery()` = `static::query()->forNode($node)`. Conditions are added at `prepare()` time.

| Scope | Selects |
|---|---|
| `roots()` | level 1 (no node needed) |
| `children()` | direct children; `+ includeDescendants()`: whole subtree |
| `parent()` | direct parent; `+ includeAncestors()`: every ancestor |
| `siblings()` | same parent, same level; `+ next()` / `+ previous()`: after / before the node |
| `excludingSelf()`, `excludingDescendants()` | everything except the node / its descendants (combinable) |

`includeSelf()` adds the node itself: with `children()` only together with `includeDescendants()`, with `parent()` only together with `includeAncestors()`, always with `siblings()`.

Scopes other than `roots()` need a node (`forNode()` / `relativeQuery()`); without node they add no condition.

Order: when no `orderBy()` is set, `left` ASC (`natural()`), `reverse()` = DESC; `previous()` defaults to DESC (closest first), `reverse()` flips it. Columns are qualified with the query alias when `alias()` is set.

Measured (children `A1`…`A10` of `A`, `A2x` under `A2`): `children()` of `A` → 10, `A1` … `A10`; `children()->includeSelf()` of `A2` → `A2x`; `children()->includeDescendants()->includeSelf()` of `A2` → `A2,A2x`; `parent()->includeAncestors()` of `A2x` → `A,A2`; `siblings()->previous()->one()` of `A5` → `A4`; `roots()->reverse()->one()` → last root; `alias('t')->forNode($a)->children()` → 10.

## `TreeHelper` (static, no database)

| Method | Returns |
|---|---|
| `convert(string\|Matrix)` | path → `Matrix`, `Matrix` → path |
| `getLeft()`, `getRight()`, `getLevel()`, `getLastSegment()`, `getBasePath()`, `getParent()` | node values from a path or matrix (`getParent()` of a root → `null`) |
| `getAncestorPaths(string $path)`, `isAncestorOf(string $ancestor, string $descendant)` | ancestry without query |
| `buildMoveMatrix()`, `buildSegmentMatrix()`, `buildBumpMatrix()`, `getRootMatrix()` | matrices used by the moves; segment ≤ 0 → `InvalidSegmentException` |

`PATH_SEPARATOR` = `.`.
