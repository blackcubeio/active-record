<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Scope;

use BadMethodCallException;
use Blackcube\ActiveRecord\ScopeParameters;
use Blackcube\ActiveRecord\Tests\Support\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ScopeTester;
use Blackcube\ActiveRecord\Tests\Support\TestActiveScope;
use Blackcube\ActiveRecord\Tests\Support\TestLanguageScope;
use Blackcube\ActiveRecord\Tests\Support\TestModel;
use Blackcube\ActiveRecord\Tests\Support\TestScopableQuery;
use Blackcube\ActiveRecord\Tests\Support\TestStateScope;

class ScopeCest
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
        $rows = [
            [
                'title' => 'Active FR',
                'active' => true,
                'languageId' => 'fr',
            ],
            [
                'title' => 'Active EN',
                'active' => true,
                'languageId' => 'en',
            ],
            [
                'title' => 'Inactive FR',
                'active' => false,
                'languageId' => 'fr',
            ],
            [
                'title' => 'Inactive EN',
                'active' => false,
                'languageId' => 'en',
            ],
            [
                'title' => 'Active No Lang',
                'active' => true,
                'languageId' => null,
            ],
        ];

        foreach ($rows as $row) {
            $model = new TestModel();
            $model->setTitle($row['title']);
            $model->setActive($row['active']);
            $model->setLanguageId($row['languageId']);
            $model->save();
        }
    }


    public function testScopeParametersReturnsValue(ScopeTester $I): void
    {
        $params = new ScopeParameters([
            'active' => true,
            'languageId' => 'fr',
        ]);
        $I->assertTrue($params->value('active'));
        $I->assertEquals('fr', $params->value('languageId'));
    }

    public function testScopeParametersReturnsNullForMissing(ScopeTester $I): void
    {
        $params = new ScopeParameters(['active' => true]);
        $I->assertNull($params->value('languageId'));
    }

    public function testScopeParametersEmptyArray(ScopeTester $I): void
    {
        $params = new ScopeParameters([]);
        $I->assertNull($params->value('anything'));
    }


    public function testAddScope(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $I->assertTrue($query->hasScope(TestActiveScope::class));
    }

    public function testRemoveScope(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $query->removeScope(TestActiveScope::class);
        $I->assertFalse($query->hasScope(TestActiveScope::class));
    }

    public function testHasScopeReturnsFalseWhenNotRegistered(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $I->assertFalse($query->hasScope(TestActiveScope::class));
    }

    public function testAddScopeChaining(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $result = $query->addScope(TestActiveScope::class)
                        ->addScope(TestLanguageScope::class);
        $I->assertTrue($query->hasScope(TestActiveScope::class));
        $I->assertTrue($query->hasScope(TestLanguageScope::class));
        $I->assertSame($query, $result);
    }


    public function testCallUnknownScopeThrows(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $I->expectThrowable(BadMethodCallException::class, function () use ($query) {
            $query->nonExistent();
        });
    }

    public function testCallReturnsQueryForChaining(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $result = $query->active(active: true);
        $I->assertSame($query, $result);
    }


    public function testSetStateGetState(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->setState('myKey', 'myValue');
        $I->assertEquals('myValue', $query->getState('myKey'));
    }

    public function testGetStateDefault(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $I->assertEquals('fallback', $query->getState('missing', 'fallback'));
        $I->assertNull($query->getState('missing'));
    }

    public function testStateScopeWritesState(ScopeTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestStateScope::class);
        $query->withState(key: 'referenceDate', val: '2025-06-01');
        $I->assertEquals('2025-06-01', $query->getState('referenceDate'));
    }


    public function testActiveScopeDefaultFiltersActive(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $results = $query->active()->all();
        $I->assertCount(3, $results);
        foreach ($results as $model) {
            $I->assertTrue($model->isActive());
        }
    }

    public function testActiveScopeFilterInactive(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $results = $query->active(active: false)->all();
        $I->assertCount(2, $results);
        foreach ($results as $model) {
            $I->assertFalse($model->isActive());
        }
    }


    public function testLanguageScopeFiltersFr(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestLanguageScope::class);
        $results = $query->language(languageId: 'fr')->all();
        $I->assertCount(2, $results);
        foreach ($results as $model) {
            $I->assertEquals('fr', $model->getLanguageId());
        }
    }

    public function testLanguageScopeFiltersEn(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestLanguageScope::class);
        $results = $query->language(languageId: 'en')->all();
        $I->assertCount(2, $results);
        foreach ($results as $model) {
            $I->assertEquals('en', $model->getLanguageId());
        }
    }

    public function testLanguageScopeNoParamSkips(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestLanguageScope::class);
        $results = $query->language()->all();
        $I->assertCount(5, $results);
    }


    public function testChainedActiveAndLanguage(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class)
              ->addScope(TestLanguageScope::class);
        $results = $query->active()->language(languageId: 'fr')->all();
        $I->assertCount(1, $results);
        $I->assertEquals('Active FR', $results[0]->getTitle());
    }

    public function testChainedInactiveAndLanguage(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class)
              ->addScope(TestLanguageScope::class);
        $results = $query->active(active: false)->language(languageId: 'en')->all();
        $I->assertCount(1, $results);
        $I->assertEquals('Inactive EN', $results[0]->getTitle());
    }


    public function testScopeWithManualWhere(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $results = $query->active()->andWhere([
            'like',
            'title',
            'FR',
        ])->all();
        $I->assertCount(1, $results);
        $I->assertEquals('Active FR', $results[0]->getTitle());
    }


    public function testScopeWithOne(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class)
              ->addScope(TestLanguageScope::class);
        $model = $query->active(active: false)->language(languageId: 'fr')->one();
        $I->assertNotNull($model);
        $I->assertEquals('Inactive FR', $model->getTitle());
    }

    public function testScopeWithCount(ScopeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $count = $query->active()->count();
        $I->assertEquals(3, $count);
    }
}
