<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\BatchedEager;

use Blackcube\ActiveRecord\Tests\Support\BatchedEager\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Detail;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Label;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\Owner;
use Blackcube\ActiveRecord\Tests\Support\BatchedEager\OwnerLabel;
use Blackcube\ActiveRecord\Tests\Support\BatchedEagerTester;

/**
 * Covers BatchedEachQueryTrait::each() with eager-loaded relations.
 *
 * Each test reads through each(20) — a batch size SMALLER than the row count (50) —
 * to prove the reader walks all batches up to the last partial one, and that with()
 * relations are populated per batch (eager, not N+1). Tables are recreated fresh
 * before each test by DatabaseCestTrait.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class BatchedEagerCest
{
    use DatabaseCestTrait;

    /**
     * @return int[] the created owner ids
     */
    private function seedOwners(int $count): array
    {
        $ownerIds = [];
        for ($i = 1; $i <= $count; $i++) {
            $owner = new Owner();
            $owner->setName('Owner ' . $i);
            $owner->save();
            $ownerIds[] = $owner->getId();
        }
        return $ownerIds;
    }

    public function testHasOneBatchedEager(BatchedEagerTester $I): void
    {
        $ownerIds = $this->seedOwners(50);
        foreach ($ownerIds as $index => $ownerId) {
            $detail = new Detail();
            $detail->setOwnerId($ownerId);
            $detail->setLabel('Detail ' . ($index + 1));
            $detail->save();
        }

        $ownerCount = 0;
        $detailCount = 0;
        $query = Owner::query()->with('detail');
        /** @var Owner $owner */
        foreach ($query->each(20) as $owner) {
            $ownerCount++;
            $I->assertTrue($owner->isRelationPopulated('detail'));
            if ($owner->relation('detail') !== null) {
                $detailCount++;
            }
        }

        $I->assertEquals(50, $ownerCount);
        $I->assertEquals(50, $detailCount);
    }

    public function testHasManyBatchedEager(BatchedEagerTester $I): void
    {
        $ownerIds = $this->seedOwners(50);
        for ($d = 1; $d <= 80; $d++) {
            $detail = new Detail();
            $detail->setOwnerId($ownerIds[($d - 1) % 50]);
            $detail->setLabel('Detail ' . $d);
            $detail->save();
        }

        $ownerCount = 0;
        $detailTotal = 0;
        $query = Owner::query()->with('details');
        /** @var Owner $owner */
        foreach ($query->each(20) as $owner) {
            $ownerCount++;
            $I->assertTrue($owner->isRelationPopulated('details'));
            $detailTotal += count($owner->relation('details'));
        }

        $I->assertEquals(50, $ownerCount);
        $I->assertEquals(80, $detailTotal);
    }

    public function testViaBatchedEager(BatchedEagerTester $I): void
    {
        $ownerIds = $this->seedOwners(50);
        for ($k = 0; $k < 80; $k++) {
            $label = new Label();
            $label->setName('Label ' . ($k + 1));
            $label->save();

            $ownerLabel = new OwnerLabel();
            $ownerLabel->setOwnerId($ownerIds[$k % 50]);
            $ownerLabel->setLabelId($label->getId());
            $ownerLabel->save();
        }

        $ownerCount = 0;
        $labelTotal = 0;
        $query = Owner::query()->with('labels');
        /** @var Owner $owner */
        foreach ($query->each(20) as $owner) {
            $ownerCount++;
            $I->assertTrue($owner->isRelationPopulated('labels'));
            $labelTotal += count($owner->relation('labels'));
        }

        $I->assertEquals(50, $ownerCount);
        $I->assertEquals(80, $labelTotal);
    }
}
