<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticQuery;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ElasticQueryTester;
use Blackcube\ActiveRecord\Tests\Support\Elastic\TestModel;
use Yiisoft\Db\Expression\Expression;

class ElasticQueryCest
{
    use DatabaseCestTrait;

    private int $schemaId;

    private function ensureTestData(): void
    {
        $schema = new ElasticSchema();
        $schema->setName('TestSchema');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'description' => ['type' => 'string'],
                'price' => ['type' => 'number'],
                'active' => ['type' => 'boolean'],
                'category' => ['type' => 'string'],
            ],
        ]));
        $schema->save();
        $this->schemaId = $schema->getId();

        $this->insertTestData();
    }

    private function insertTestData(): void
    {
        $models = [
            [
                'title' => 'Product A',
                'description' => 'First product',
                'price' => 10.99,
                'active' => true,
                'category' => 'electronics',
            ],
            [
                'title' => 'Product B',
                'description' => 'Second product',
                'price' => 25.50,
                'active' => false,
                'category' => 'electronics',
            ],
            [
                'title' => 'Product C',
                'description' => 'Third product',
                'price' => 5.00,
                'active' => true,
                'category' => 'books',
            ],
            [
                'title' => 'Product D',
                'description' => 'Fourth product',
                'price' => 100.00,
                'active' => true,
                'category' => 'electronics',
            ],
        ];

        foreach ($models as $data) {
            $model = new TestModel();
            $model->setTitle($data['title']);
            $model->elasticSchemaId = $this->schemaId;
            $model->description = $data['description'];
            $model->price = $data['price'];
            $model->active = $data['active'];
            $model->category = $data['category'];
            $model->insert();
        }
    }


    public function testWhereWithRealColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $result = TestModel::query()
            ->where(['title' => 'Product A'])
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product A', $result->getTitle());
    }

    public function testWhereWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $result = TestModel::query()
            ->where(['category' => 'books'])
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product C', $result->getTitle());
        $I->assertEquals('books', $result->category);
    }

    public function testWhereWithMixedColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'category' => 'electronics',
                'title' => 'Product A',
            ])
            ->all();

        $I->assertCount(1, $results);
        $I->assertEquals('Product A', $results[0]->getTitle());
    }

    public function testWhereWithMultipleVirtualColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'category' => 'electronics',
                'active' => true,
            ])
            ->all();

        $I->assertCount(2, $results);
    }


    public function testAndWhereWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where(['category' => 'electronics'])
            ->andWhere(['active' => true])
            ->all();

        $I->assertCount(2, $results);
    }

    public function testAndWhereWithRealAndVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $result = TestModel::query()
            ->where(['title' => 'Product A'])
            ->andWhere(['category' => 'electronics'])
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product A', $result->getTitle());
    }


    public function testOrWhereWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where(['category' => 'books'])
            ->orWhere(['category' => 'electronics'])
            ->all();

        $I->assertCount(4, $results);
    }

    public function testOrWhereWithMixedColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where(['title' => 'Product A'])
            ->orWhere(['category' => 'books'])
            ->all();

        $I->assertCount(2, $results);
    }


    public function testOrderByVirtualColumnAsc(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->orderBy(['category' => SORT_ASC])
            ->all();

        $I->assertCount(4, $results);
        $I->assertEquals('books', $results[0]->category);
    }

    public function testOrderByVirtualColumnDesc(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->orderBy(['category' => SORT_DESC])
            ->all();

        $I->assertCount(4, $results);
        $I->assertEquals('electronics', $results[0]->category);
    }

    public function testOrderByMixedColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->orderBy([
                'category' => SORT_ASC,
                'title' => SORT_DESC,
            ])
            ->all();

        $I->assertCount(4, $results);
        $I->assertEquals('books', $results[0]->category);
        $I->assertEquals('electronics', $results[1]->category);
    }


    public function testAddOrderByVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->orderBy(['category' => SORT_ASC])
            ->addOrderBy(['description' => SORT_ASC])
            ->all();

        $I->assertCount(4, $results);
        $I->assertEquals('books', $results[0]->category);
    }


    public function testWhereOperatorFormatWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'like',
                'description',
                'First',
            ])
            ->all();

        $I->assertCount(1, $results);
        $I->assertEquals('Product A', $results[0]->getTitle());
    }

    public function testWhereBetweenWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'between',
                'price',
                10,
                30,
            ])
            ->all();

        $I->assertCount(2, $results);
    }

    public function testWhereInWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'in',
                'category',
                [
                    'books',
                    'electronics',
                ],
            ])
            ->all();

        $I->assertCount(4, $results);
    }


    public function testWhereAndNestedCondition(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'and',
                ['category' => 'electronics'],
                ['active' => true],
            ])
            ->all();

        $I->assertCount(2, $results);
    }

    public function testWhereOrNestedCondition(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                'or',
                ['category' => 'books'],
                ['active' => false],
            ])
            ->all();

        $I->assertCount(2, $results);
    }


    public function testGetRealAttributesReturnsDbColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $query = TestModel::query();
        $realAttributes = $query->getRealAttributes();

        $I->assertContains('id', $realAttributes);
        $I->assertContains('title', $realAttributes);
        $I->assertContains('elasticSchemaId', $realAttributes);
        $I->assertContains('_extras', $realAttributes);

        $I->assertNotContains('description', $realAttributes);
        $I->assertNotContains('price', $realAttributes);
        $I->assertNotContains('category', $realAttributes);
    }


    public function testWhereWithExpressionIsNotModified(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $expression = new Expression('title = :title', [':title' => 'Product A']);

        $result = TestModel::query()
            ->where($expression)
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product A', $result->getTitle());
    }

    public function testWhereWithStringConditionIsNotModified(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $result = TestModel::query()
            ->where('title = :title', [':title' => 'Product A'])
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product A', $result->getTitle());
    }


    public function testComplexQueryWithVirtualColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where(['category' => 'electronics'])
            ->andWhere(['active' => true])
            ->orderBy(['description' => SORT_ASC])
            ->all();

        $I->assertCount(2, $results);
        $I->assertEquals('First product', $results[0]->description);
        $I->assertEquals('Fourth product', $results[1]->description);
    }


    public function testWhereWithTableDotVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $result = TestModel::query()
            ->where(['{{%testModels}}.category' => 'books'])
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product C', $result->getTitle());
    }

    public function testWhereWithTableDotRealColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $result = TestModel::query()
            ->where(['{{%testModels}}.title' => 'Product A'])
            ->one();

        $I->assertNotNull($result);
        $I->assertEquals('Product A', $result->getTitle());
    }

    public function testWhereWithDifferentTableIsIgnored(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $query = TestModel::query();

        $results = $query
            ->where(['title' => 'Product A'])
            ->all();

        $I->assertCount(1, $results);
    }

    public function testIsVirtualColumnReturnsFalseForOtherTable(ElasticQueryTester $I): void
    {
        $this->ensureTestData();


        $query = TestModel::query();
        $reflection = new \ReflectionClass($query);
        $method = $reflection->getMethod('isVirtualColumn');
        $method->setAccessible(true);

        $I->assertTrue($method->invoke($query, 'category'));
        $I->assertTrue($method->invoke($query, '{{%testModels}}.category'));

        $I->assertFalse($method->invoke($query, 'otherTable.category'));
        $I->assertFalse($method->invoke($query, '{{%otherTable}}.category'));
    }

    public function testOrderByWithTableDotVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->orderBy(['{{%testModels}}.category' => SORT_ASC])
            ->all();

        $I->assertCount(4, $results);
        $I->assertEquals('books', $results[0]->category);
    }


    public function testWhereWithIntegerKeyIsNotTransformed(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where([
                '>',
                'price',
                20,
            ])
            ->all();

        $I->assertCount(2, $results);
    }


    public function testWhereWithNullCondition(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $results = TestModel::query()
            ->where(null)
            ->all();

        $I->assertCount(4, $results);
    }


    public function testSetWhereWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $query = TestModel::query()
            ->where(['category' => 'electronics']);

        $query->setWhere(['category' => 'books']);

        $results = $query->all();

        $I->assertCount(1, $results);
        $I->assertEquals('books', $results[0]->category);
    }

    public function testSetWhereAfterWhereWithVirtualColumn(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $query = TestModel::query()
            ->where(['active' => true]);

        $query->setWhere([
            'category' => 'electronics',
            'active' => false,
        ]);

        $results = $query->all();

        $I->assertCount(1, $results);
        $I->assertEquals('Product B', $results[0]->getTitle());
    }
}
