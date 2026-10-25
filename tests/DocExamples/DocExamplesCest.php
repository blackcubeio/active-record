<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\DocExamples;

use BadMethodCallException;
use Blackcube\ActiveRecord\ScopeParameters;
use Blackcube\ActiveRecord\Tests\Support\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\DocExamplesTester;
use Blackcube\ActiveRecord\Tests\Support\TestActiveScope;
use Blackcube\ActiveRecord\Tests\Support\TestAtDateScope;
use Blackcube\ActiveRecord\Tests\Support\TestAvailableScope;
use Blackcube\ActiveRecord\Tests\Support\TestLanguageScope;
use Blackcube\ActiveRecord\Tests\Support\TestModel;
use Blackcube\ActiveRecord\Tests\Support\TestPreloadedModel;
use Blackcube\ActiveRecord\Tests\Support\TestScopableQuery;

/**
 * Tests covering all examples from docs/api.md and docs/integration.md.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class DocExamplesCest
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


    /**
     * docs/api.md: ScopeParameters example
     * $params = new ScopeParameters(['active' => false]);
     * $params->value('active'); // false
     * $params->value('missing'); // null
     */
    public function testScopeParametersDocExample(DocExamplesTester $I): void
    {
        $params = new ScopeParameters(['active' => false]);
        $I->assertFalse($params->value('active'));
        $I->assertNull($params->value('missing'));
    }


    /**
     * docs/api.md: ScopableTrait dispatch
     * $query->active(active: false);
     * Throws BadMethodCallException if no scope matches.
     */
    public function testCallDispatchDocExample(DocExamplesTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);

        $result = $query->active(active: false);
        $I->assertSame($query, $result);

        $I->expectThrowable(BadMethodCallException::class, function () {
            $q = TestScopableQuery::create(TestModel::class);
            $q->nonExistent();
        });
    }


    /**
     * docs/api.md: QualifyColumnTrait
     * $query->andWhere(['active' => true]); → WHERE `testModels`.`active` = TRUE
     * $query->alias('p')->andWhere(['active' => true]); → WHERE p.`active` = TRUE
     */
    public function testQualifyColumnDocExample(DocExamplesTester $I): void
    {
        $sql = TestScopableQuery::create(TestModel::class)
            ->andWhere(['active' => true])
            ->createCommand()
            ->getRawSql();
        $I->assertStringContainsString('`testModels`.`active`', $sql);

        $sql = TestScopableQuery::create(TestModel::class)
            ->alias('p')
            ->andWhere(['active' => true])
            ->createCommand()
            ->getRawSql();
        $I->assertStringContainsString('p.`active`', $sql);
    }


    /**
     * docs/integration.md: ProductQuery with scopes in create()
     * TestPreloadedQuery::create() pre-registers ActiveScope + LanguageScope
     */
    public function testPreloadedQueryHasScopes(DocExamplesTester $I): void
    {
        $query = TestPreloadedModel::query();
        $I->assertTrue($query->hasScope(TestActiveScope::class));
        $I->assertTrue($query->hasScope(TestLanguageScope::class));
    }


    /**
     * docs/integration.md:
     * $products = Product::query()->active()->all();
     */
    public function testModelQueryActiveAll(DocExamplesTester $I): void
    {
        $this->ensureTestData();
        $results = TestPreloadedModel::query()->active()->all();
        $I->assertCount(3, $results);
        foreach ($results as $model) {
            $I->assertTrue($model->isActive());
        }
    }

    /**
     * docs/integration.md:
     * $products = Product::query()
     *     ->active(active: false)
     *     ->language(languageId: 'fr')
     *     ->all();
     */
    public function testModelQueryActivefalseLanguageFr(DocExamplesTester $I): void
    {
        $this->ensureTestData();
        $results = TestPreloadedModel::query()
            ->active(active: false)
            ->language(languageId: 'fr')
            ->all();
        $I->assertCount(1, $results);
        $I->assertEquals('Inactive FR', $results[0]->getTitle());
    }

    /**
     * docs/integration.md:
     * $products = Product::query()
     *     ->active()
     *     ->andWhere(['like', 'title', 'laptop'])
     *     ->all();
     */
    public function testModelQueryActivePlusManualWhere(DocExamplesTester $I): void
    {
        $this->ensureTestData();
        $results = TestPreloadedModel::query()
            ->active()
            ->andWhere([
                'like',
                'title',
                'FR',
            ])
            ->all();
        $I->assertCount(1, $results);
        $I->assertEquals('Active FR', $results[0]->getTitle());
    }


    /**
     * docs/integration.md:
     * $query->atDate(date: '2025-06-01')->available();
     * AtDateScope stores date in state, AvailableScope reads it.
     */
    public function testSharedStateBetweenScopes(DocExamplesTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestAtDateScope::class)
              ->addScope(TestAvailableScope::class);

        $query->atDate(date: '2025-06-01')->available();

        $I->assertEquals('2025-06-01', $query->getState('referenceDate'));
        $I->assertEquals('2025-06-01', $query->getState('availableUsedDate'));
    }


    /**
     * docs/api.md: addScope / removeScope / hasScope
     */
    public function testScopeRegistrationApi(DocExamplesTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);

        $I->assertFalse($query->hasScope(TestActiveScope::class));

        $result = $query->addScope(TestActiveScope::class);
        $I->assertSame($query, $result);
        $I->assertTrue($query->hasScope(TestActiveScope::class));

        $query->removeScope(TestActiveScope::class);
        $I->assertFalse($query->hasScope(TestActiveScope::class));
    }

    /**
     * docs/api.md: setState / getState
     */
    public function testStateApi(DocExamplesTester $I): void
    {
        $query = TestScopableQuery::create(TestModel::class);

        $I->assertNull($query->getState('key'));
        $I->assertEquals('default', $query->getState('key', 'default'));

        $result = $query->setState('key', 'value');
        $I->assertSame($query, $result);
        $I->assertEquals('value', $query->getState('key'));
    }
}
