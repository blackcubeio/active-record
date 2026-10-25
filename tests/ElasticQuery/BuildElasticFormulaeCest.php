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

class BuildElasticFormulaeCest
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
                'body' => ['type' => 'string'],
                'price' => ['type' => 'number'],
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
                'title' => 'test test product',
                'description' => 'test test test item',
                'body' => 'test content',
                'price' => 10,
            ],
            [
                'title' => 'test product',
                'description' => 'test item',
                'body' => 'no match',
                'price' => 20,
            ],
            [
                'title' => 'no match',
                'description' => 'nothing here',
                'body' => 'empty',
                'price' => 30,
            ],
        ];

        foreach ($models as $data) {
            $model = new TestModel();
            $model->setTitle($data['title']);
            $model->elasticSchemaId = $this->schemaId;
            $model->description = $data['description'];
            $model->body = $data['body'];
            $model->price = $data['price'];
            $model->insert();
        }
    }


    public function testBuildFormulaeWithVirtualColumnsOnly(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestModel::query();
        $relevance = $query->buildFormulae($occurrences, [
            'description',
            'body',
        ], [':search' => 'test']);

        $results = $query
            ->select([
                '{{%testModels}}.[[title]]',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->orderBy(['relevance' => SORT_DESC])
            ->asArray()
            ->all();

        $I->assertCount(2, $results);
        $I->assertEquals('test test product', $results[0]['title']);
        $I->assertEquals('test product', $results[1]['title']);
    }

    public function testBuildFormulaeWithMixedColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestModel::query();
        $relevance = $query->buildFormulae($occurrences, [
            'title',
            'description',
            'body',
        ], [':search' => 'test']);

        $results = $query
            ->select([
                '{{%testModels}}.[[title]]',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->orderBy(['relevance' => SORT_DESC])
            ->asArray()
            ->all();

        $I->assertCount(2, $results);
        $I->assertEquals('test test product', $results[0]['title']);
        $I->assertEquals('test product', $results[1]['title']);
    }

    public function testBuildFormulaeWithRealColumnsOnly(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestModel::query();
        $relevance = $query->buildFormulae($occurrences, ['title'], [':search' => 'test']);

        $results = $query
            ->select([
                '{{%testModels}}.[[title]]',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->orderBy(['relevance' => SORT_DESC])
            ->asArray()
            ->all();

        $I->assertCount(2, $results);
        $I->assertEquals('test test product', $results[0]['title']);
        $I->assertEquals('test product', $results[1]['title']);
    }

    public function testBuildFormulaeNoMatchReturnsEmpty(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestModel::query();
        $relevance = $query->buildFormulae($occurrences, [
            'title',
            'description',
        ], [':search' => 'nonexistent']);

        $results = $query
            ->select([
                '{{%testModels}}.[[title]]',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->asArray()
            ->all();

        $I->assertCount(0, $results);
    }


    public function testBuildFormulaeQualifiesColumns(ElasticQueryTester $I): void
    {
        $this->ensureTestData();

        $capture = [];
        $spy = function (string $col) use (&$capture) {
            $capture[] = $col;
            return 'CHAR_LENGTH(' . $col . ')';
        };

        $query = TestModel::query();
        $query->buildFormulae($spy, [
            'title',
            'description',
        ], []);

        $I->assertCount(2, $capture);
        $I->assertStringContainsString('JSON_VALUE', $capture[0]);
        $I->assertStringContainsString('testModels', $capture[0]);
        $I->assertStringContainsString('description', $capture[0]);
        $I->assertStringContainsString('testModels', $capture[1]);
        $I->assertStringContainsString('title', $capture[1]);
    }
}
