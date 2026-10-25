<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\MagicCompose;

use Blackcube\ActiveRecord\Tests\Support\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\MagicComposeTester;
use Blackcube\ActiveRecord\Tests\Support\TestActiveScope;
use Blackcube\ActiveRecord\Tests\Support\TestComposedModel;
use Blackcube\ActiveRecord\Tests\Support\TestScopableQuery;

/**
 * Tests for MagicComposeActiveRecordTrait in the active-record package.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class MagicComposeCest
{
    use DatabaseCestTrait;

    private static bool $dataInserted = false;

    private function ensureTestData(): void
    {
        if (self::$dataInserted) {
            return;
        }
        $this->insertTestData();
        self::$dataInserted = true;
    }

    private function insertTestData(): void
    {
        $this->db->createCommand("INSERT INTO `testComposedModels` (`title`, `active`, `languageId`, `extraA`) VALUES ('Composed A', true, 'fr', 'valA')")->execute();
        $this->db->createCommand("INSERT INTO `testComposedModels` (`title`, `active`, `languageId`, `extraA`) VALUES ('Composed B', false, 'en', 'valB')")->execute();
        $this->db->createCommand("INSERT INTO `testComposedModels` (`title`, `active`, `languageId`, `extraA`) VALUES ('Composed C', true, 'en', NULL)")->execute();
    }


    public function testPopulateRecordChainExecutes(MagicComposeTester $I): void
    {
        $this->ensureTestData();
        $loaded = TestComposedModel::query()->where(['title' => 'Composed A'])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('Composed A', $loaded->getTitle());
        $I->assertContains('populateRecord:before', $loaded->traitALog);
        $I->assertContains('populateRecord:after', $loaded->traitALog);
    }

    public function testPopulateRecordLoadsExtraColumn(MagicComposeTester $I): void
    {
        $this->ensureTestData();
        $loaded = TestComposedModel::query()->where(['title' => 'Composed A'])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('valA', $loaded->extraA);
    }


    public function testPopulatePropertyInterceptsExtraColumn(MagicComposeTester $I): void
    {
        $this->ensureTestData();
        $loaded = TestComposedModel::query()->where(['title' => 'Composed A'])->one();

        $I->assertNotNull($loaded);
        $I->assertContains('populateProperty:extraA', $loaded->traitALog);
    }

    public function testPopulatePropertyDelegatesNativeColumns(MagicComposeTester $I): void
    {
        $this->ensureTestData();
        $loaded = TestComposedModel::query()->where(['title' => 'Composed A'])->one();

        $I->assertNotNull($loaded);
        $I->assertContains('populateProperty:title', $loaded->traitALog);
        $I->assertEquals('Composed A', $loaded->getTitle());
    }


    public function testPropertyValuesInternalIncludesExtra(MagicComposeTester $I): void
    {
        $model = new TestComposedModel();
        $model->setTitle('Save Test');
        $model->setActive(true);
        $model->extraA = 'saved_value';
        $model->save();

        $I->assertContains('propertyValues:before', $model->traitALog);
        $I->assertContains('propertyValues:after', $model->traitALog);

        $loaded = TestComposedModel::query()->where(['title' => 'Save Test'])->one();
        $I->assertNotNull($loaded);
        $I->assertEquals('saved_value', $loaded->extraA);
    }

    public function testSaveAndLoadRoundtrip(MagicComposeTester $I): void
    {
        $model = new TestComposedModel();
        $model->setTitle('Roundtrip');
        $model->setActive(false);
        $model->setLanguageId('de');
        $model->extraA = 'roundtrip_val';
        $model->save();

        $loaded = TestComposedModel::query()->where(['title' => 'Roundtrip'])->one();
        $I->assertNotNull($loaded);
        $I->assertEquals('Roundtrip', $loaded->getTitle());
        $I->assertFalse($loaded->isActive());
        $I->assertEquals('de', $loaded->getLanguageId());
        $I->assertEquals('roundtrip_val', $loaded->extraA);
    }


    public function testRefreshInternalChain(MagicComposeTester $I): void
    {
        $model = new TestComposedModel();
        $model->setTitle('Refresh Test');
        $model->extraA = 'original';
        $model->save();

        $this->db->createCommand('UPDATE `testComposedModels` SET `extraA` = :value WHERE `id` = :id')
            ->bindValue(':value', 'updated')
            ->bindValue(':id', $model->getId())
            ->execute();

        $model->clearTraitALog();
        $model->refresh();

        $I->assertEquals('updated', $model->extraA);
        $I->assertContains('refreshInternal:before', $model->traitALog);
        $I->assertContains('refreshInternal:after', $model->traitALog);
    }


    public function testDeleteInternalChain(MagicComposeTester $I): void
    {
        $model = new TestComposedModel();
        $model->setTitle('Delete Test');
        $model->extraA = 'to_delete';
        $model->save();

        $id = $model->getId();
        $I->assertNotNull($id);

        $model->clearTraitALog();
        $model->delete();

        $I->assertContains('deleteInternal:before', $model->traitALog);
        $I->assertContains('deleteInternal:after', $model->traitALog);

        $found = TestComposedModel::query()->where(['id' => $id])->one();
        $I->assertNull($found);
    }


    public function testMagicGetterSetter(MagicComposeTester $I): void
    {
        $model = new TestComposedModel();
        $model->extraA = 'magic_value';
        $I->assertEquals('magic_value', $model->extraA);
    }


    public function testComposedModelWithScopes(MagicComposeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestComposedModel::class);
        $query->addScope(TestActiveScope::class);
        $results = $query->active()->andWhere([
            'like',
            'title',
            'Composed',
        ])->all();

        $I->assertCount(2, $results);
        foreach ($results as $model) {
            $I->assertTrue($model->isActive());
        }
    }
}
