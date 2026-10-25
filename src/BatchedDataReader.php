<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord;

use Closure;
use LogicException;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\DataReaderInterface;

/**
 * Flattens a BatchQueryResult into a per-model DataReader: the query is read in
 * batches (so with()/eager loading runs once per batch, not once per row), and
 * the models are exposed one by one. Iterator with explicit state, no generator,
 * so it stays a real rewindable DataReaderInterface.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class BatchedDataReader implements DataReaderInterface
{
    /** @var array<int, array|object> */
    private array $currentBatch = [];
    private int $position = 0;
    private int $key = 0;

    public function __construct(private BatchQueryResultInterface $batchResult)
    {
    }

    public function rewind(): void
    {
        $this->batchResult->rewind();
        $this->key = 0;
        $this->loadCurrentBatch();
    }

    public function next(): void
    {
        $this->position++;
        $this->key++;
        if (isset($this->currentBatch[$this->position]) === false) {
            $this->batchResult->next();
            $this->loadCurrentBatch();
        }
    }

    public function valid(): bool
    {
        return isset($this->currentBatch[$this->position]) === true;
    }

    public function current(): array|object|false
    {
        return $this->currentBatch[$this->position] ?? false;
    }

    public function key(): int|string|null
    {
        return $this->valid() === true ? $this->key : null;
    }

    public function count(): int
    {
        throw new LogicException('count() is not supported on a batched data reader.');
    }

    public function indexBy(Closure|string|null $indexBy): static
    {
        throw new LogicException('indexBy() is not supported on a batched data reader.');
    }

    public function resultCallback(?Closure $resultCallback): static
    {
        throw new LogicException('resultCallback() is not supported on a batched data reader.');
    }

    public function typecastColumns(array $typecastColumns): static
    {
        throw new LogicException('typecastColumns() is not supported on a batched data reader.');
    }

    private function loadCurrentBatch(): void
    {
        $this->position = 0;
        $this->currentBatch = $this->batchResult->valid() === true
            ? array_values($this->batchResult->current())
            : [];
    }
}
