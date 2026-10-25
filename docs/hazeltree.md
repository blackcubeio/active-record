# Hazeltree — Nested Sets with Rational Numbers

> **Attribution**
>
> Hazeltree implements **Dan Hazel**'s research (2008): *"Using rational numbers to key nested sets"*.
> Builds on Vadim Tropashko's work (2005) and Joe Celko's nested sets.
>
> [Read the original paper on arXiv](https://arxiv.org/abs/0806.3115)

Tree structure in a relational database. Read entire branches in one query. Write without global renumbering.

## How It Works

### Storage

| Column | Purpose |
|--------|---------|
| `path` | Dot notation path (e.g., `1.2.3`) |
| `left` | Left boundary value (rational number) |
| `right` | Right boundary value (rational number) |
| `level` | Depth in tree (1 = root) |

Instead of sequential integers, Hazeltree uses rational fractions derived from 2x2 matrices. There is always room between two values to insert a new element. No global renumbering.

### Architecture

```
TreeHelper              — path/matrix conversion, left/right calculation
Matrix                  — immutable 2x2 matrix with fluent interface
BaseHazeltreeTrait      — prefixed methods (tryHazeltreeGet, hazeltreePopulateProperty, ...)
HazeltreeTrait          — composite: __get/__set dispatch + AR overrides + tree operations
HazeltreeInterface      — contract for tree models
BaseHazeltreeQueryTrait — tree navigation (children, parent, siblings, roots)
HazeltreeQueryTrait     — composite query trait
```

## Setup

### Model

```php
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeTrait;
use Yiisoft\ActiveRecord\ActiveRecord;

class Menu extends ActiveRecord implements HazeltreeInterface
{
    use HazeltreeTrait;

    protected int $id;
    protected string $name = '';

    public function tableName(): string
    {
        return '{{%menus}}';
    }

    public static function query($modelClass = null): ActiveQueryInterface
    {
        return new MenuQuery($modelClass ?? static::class);
    }
}
```

No `MagicComposeActiveRecordTrait` needed.

### Query class

```php
use Blackcube\ActiveRecord\Hazeltree\BaseHazeltreeQueryTrait;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeQueryInterface;
use Yiisoft\ActiveRecord\ActiveQuery;

class MenuQuery extends ActiveQuery implements HazeltreeQueryInterface
{
    use BaseHazeltreeQueryTrait;
}
```

Or extend the abstract:

```php
use Blackcube\ActiveRecord\AbstractHazeltreeQuery;

class MenuQuery extends AbstractHazeltreeQuery
{
    // scopes, etc.
}
```

## Writing

### Create root nodes

```php
$home = new Menu();
$home->name = 'Home';
$home->save(); // Automatically gets path '1'

$blog = new Menu();
$blog->name = 'Blog';
$blog->save(); // path '2'
```

### Create children

```php
$about = new Menu();
$about->name = 'About';
$about->saveInto($home); // path '1.1'

$contact = new Menu();
$contact->name = 'Contact';
$contact->saveInto($home); // path '1.2'
```

### Insert before / after

```php
$services = new Menu();
$services->name = 'Services';
$services->saveBefore($contact); // path '1.2', contact becomes '1.3'

$faq = new Menu();
$faq->name = 'FAQ';
$faq->saveAfter($contact); // path '1.4'
```

### Move existing nodes

```php
// Move About into Blog
$about->saveInto($blog);

// Move Services before FAQ
$services->saveBefore($faq);
```

### Delete

```php
// Deletes node and all descendants, closes the gap
$services->delete();
```

## Reading

### Navigation

```php
// Direct children
$children = $home->relativeQuery()->children()->each();

// All descendants (recursive)
$descendants = $home->relativeQuery()->children()->includeDescendants()->each();

// Parent
$parent = $about->relativeQuery()->parent()->one();

// Breadcrumb (all ancestors)
$breadcrumb = $about->relativeQuery()->parent()->includeAncestors()->includeSelf()->each();

// Siblings
$siblings = $about->relativeQuery()->siblings()->each();

// Next sibling
$next = $about->relativeQuery()->siblings()->next()->one();

// Previous sibling
$prev = $about->relativeQuery()->siblings()->previous()->one();

// All roots
$roots = Menu::query()->roots()->each();
```

### Sorting

```php
// Natural order (left ASC) — default
$children = $home->relativeQuery()->children()->natural()->each();

// Reversed order (left DESC)
$children = $home->relativeQuery()->children()->reverse()->each();
```

### Exclusion

```php
// Exclude self
$others = $node->relativeQuery()->excludingSelf()->each();

// Exclude descendants
$flat = $node->relativeQuery()->excludingDescendants()->each();
```

### Level constraints

```php
$depth = $node->getSubtreeDepth();
$maxLevel = $node->getMaxLevelIfMoveInto($target);
$wouldExceed = $node->wouldExceedMaxLevel($target, 'into', 5);
```

## Combining with Elastic

For models that need both tree structure and dynamic attributes:

```php
use Blackcube\ActiveRecord\HazeltreeElasticTrait;
use Blackcube\ActiveRecord\Elastic\ElasticInterface;
use Blackcube\ActiveRecord\Hazeltree\HazeltreeInterface;

class Content extends ActiveRecord implements ElasticInterface, HazeltreeInterface
{
    use HazeltreeElasticTrait;
}
```

Query class:

```php
use Blackcube\ActiveRecord\AbstractHazeltreeElasticQuery;

class ContentQuery extends AbstractHazeltreeElasticQuery
{
    // Both elastic virtual columns and tree navigation
}
```

## API Reference

### HazeltreeInterface

| Method | Description |
|--------|-------------|
| `isRoot()` | Check if node is at level 1 |
| `canMove($targetPath)` | `false` when the target is the node itself or one of its descendants |
| `saveInto($target)` | Insert/move as last child of target |
| `saveBefore($target)` | Insert/move before target |
| `saveAfter($target)` | Insert/move after target |
| `getSubtreeDepth()` | Max depth of descendants |
| `getMaxLevelIfMoveBefore($target)` | Max level after move |
| `getMaxLevelIfMoveAfter($target)` | Max level after move |
| `getMaxLevelIfMoveInto($target)` | Max level after move |
| `wouldExceedMaxLevel($target, $mode, $max)` | Check level constraint |

### HazeltreeQueryInterface

| Method | Description |
|--------|-------------|
| `forNode($node)` | Set reference node |
| `roots()` | Query root nodes |
| `children()` | Query direct children |
| `parent()` | Query parent |
| `siblings()` | Query siblings |
| `next()` / `previous()` | Direction modifier |
| `includeDescendants()` | Include all descendants |
| `includeAncestors()` | Include all ancestors |
| `includeSelf()` | Include reference node (with `children()` only together with `includeDescendants()`, with `parent()` only together with `includeAncestors()`) |
| `excludingSelf()` | Exclude reference node |
| `excludingDescendants()` | Exclude descendants |
| `natural()` / `reverse()` | Sort direction |

### TreeHelper

| Method | Description |
|--------|-------------|
| `convert($element)` | Path string <-> Matrix conversion |
| `getParent($element)` | Parent matrix from path or matrix |
| `getLastSegment($element)` | Last segment number |
| `getLeft($element)` | Left boundary value |
| `getRight($element)` | Right boundary value |
| `getLevel($element)` | Depth level |
| `buildMoveMatrix($from, $to, $bump)` | Transformation matrix for moves |
| `getAncestorPaths($path)` | All ancestor paths without DB |
| `isAncestorOf($ancestor, $descendant)` | Check ancestry without DB |
