# ActiveRecord

Scoped queries, elastic dynamic attributes, Hazeltree nested sets — a toolkit for `yiisoft/active-record`.

## Why ActiveRecord?

| Problem | Usual approach | ActiveRecord approach |
|---------|---------------|----------------------|
| Reusable query filters | Copy-paste `andWhere()` everywhere | Named scope: `->active()` |
| Filter parameters | `mixed ...$params`, `$params[0]` | Named parameters: `active: true` |
| Ambiguous columns in JOINs | Manual `table.column` everywhere | Transparent qualification |
| Sharing state between scopes | Global variables or hacks | `setState()` / `getState()` |
| Dynamic attributes per schema | One table per type, or EAV | JSON column + JSON Schema |
| Tree structure in RDBMS | Parent-child or sequential nested sets | Rational numbers, no global renumbering |

**Scopes are named.** `->active()` not `->andWhere(['active' => true])`.

**Elastic stores JSON.** You manipulate PHP properties. Validation is automatic. Queries are transparent.

**Hazeltree reads branches in one query.** Writes only touch siblings. No cache needed.

## Prerequisites

- PHP 8.2+
- ext-intl
- MySQL/MariaDB (for JSON column support and tree queries)
- `yiisoft/active-record ^1.1`
- `yiisoft/db ^2.0`
- `yiisoft/cache ^3.2`
- `yiisoft/validator ^2.6`
- `yiisoft/event-dispatcher ^1.1`
- `swaggest/json-schema ^0.12.43`

### Optional

```bash
# MagicCompose (for models with custom composed traits)
composer require blackcube/magic-compose
```

## Caveats

**Column qualification is not magic.** It handles hash conditions, operator conditions, and nested `and`/`or`. It does NOT touch raw SQL strings or `Expression` objects.

**Elastic virtual columns use JSON_VALUE().** Performance depends on your MySQL version and JSON column indexing. For high-volume queries, consider generated columns.

**Hazeltree writes touch siblings.** Moving a node updates all following siblings and their descendants. This is much better than sequential nested sets (which update the entire tree), but it's not O(1). For very wide trees (hundreds of siblings), batch operations are noticeable.

**Only ONE composite trait per model.** You cannot combine `ElasticTrait` and `HazeltreeTrait` directly — use `HazeltreeElasticTrait` which dispatches to both.

## Table of contents

- [Installation](installation.md)
- [API — Scopes & Qualification](api.md)
- [API — Elastic](elastic.md)
- [API — Hazeltree](hazeltree.md)
- [Integration](integration.md)
