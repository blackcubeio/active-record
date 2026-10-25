# Installation

```bash
composer require blackcube/active-record
```

## Requirements

- PHP 8.2+
- ext-intl
- MySQL/MariaDB (for JSON column support and tree queries)
- `blackcube/injector ^1.0`
- `psr/log ^3.0`
- `yiisoft/active-record ^1.1`
- `yiisoft/db ^2.1`
- `yiisoft/cache ^3.2`
- `yiisoft/validator ^2.6`
- `yiisoft/event-dispatcher ^1.1`
- `swaggest/json-schema ^0.12.43`

## Database setup

### Elastic schemas table

Run the provided migration or create manually:

```sql
CREATE TABLE `elasticSchemas` (
    `id` BIGINT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL UNIQUE,
    `schema` TEXT,
    `view` VARCHAR(255),
    `dateCreate` DATETIME NOT NULL,
    `dateUpdate` DATETIME
);
```

### Model tables

For models using **Elastic**, add two columns:

```sql
ALTER TABLE `yourTable`
    ADD `elasticSchemaId` BIGINT NULL,
    ADD `_extras` JSON NULL,
    ADD FOREIGN KEY (`elasticSchemaId`) REFERENCES `elasticSchemas`(`id`) ON DELETE SET NULL;
```

Column names are customizable via `elasticColumn()` and `elasticSchemaColumn()`.

For models using **Hazeltree**, add four columns:

```sql
ALTER TABLE `yourTable`
    ADD `path` VARCHAR(255) NOT NULL UNIQUE,
    ADD `left` DECIMAL(25,22) NOT NULL,
    ADD `right` DECIMAL(25,22) NOT NULL,
    ADD `level` INT NOT NULL,
    ADD INDEX (`left`),
    ADD INDEX (`right`),
    ADD INDEX (`level`);
```

Column names are customizable via `leftColumn()`, `rightColumn()`, `pathColumn()`, `levelColumn()`.

### Optional: MagicCompose

For models using `Blackcube\MagicCompose\MagicComposeActiveRecordTrait` (provided by `blackcube/magic-compose`):

```bash
composer require blackcube/magic-compose
```

New code should use the composite traits (`ElasticTrait`, `HazeltreeTrait`, `HazeltreeElasticTrait`) directly.
