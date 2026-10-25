<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticCombinedTraits;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\CombinedTraitsModel;
use Blackcube\ActiveRecord\Tests\Support\ElasticCombinedTraitsTester;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;

/**
 * Integration tests for combining ElasticTrait with another trait that has magic methods.
 * This validates the "double exposition" pattern documented in magic-traits.md.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class CombinedTraitsCest
{
    use DatabaseCestTrait;

    private ?int $schemaId = null;

    private function ensureTestData(): void
    {
        $schema = new ElasticSchema();
        $schema->setName('CombinedTestSchema');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'count' => ['type' => 'integer'],
            ],
            'required' => ['title'],
        ]));
        $schema->save();
        $this->schemaId = $schema->getId();
    }


    public function testMockMagicSetAndGet(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();

        $model->mockLeft = 1;
        $model->mockRight = 10;
        $model->mockLevel = 2;

        $I->assertEquals(1, $model->mockLeft);
        $I->assertEquals(10, $model->mockRight);
        $I->assertEquals(2, $model->mockLevel);
    }

    public function testMockMagicIsset(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();

        $I->assertFalse(isset($model->mockLeft));

        $model->mockLeft = 5;

        $I->assertTrue(isset($model->mockLeft));
    }

    public function testMockMagicCallGetter(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->mockLeft = 3;

        $I->assertEquals(3, $model->getMockLeft());
    }

    public function testMockMagicCallSetter(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();

        $model->setMockRight(15);

        $I->assertEquals(15, $model->mockRight);
    }

    public function testMockMagicCallCustomMethod(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->mockLeft = 1;
        $model->mockRight = 2;

        $I->assertTrue($model->isLeaf());

        $model->mockRight = 10;
        $I->assertFalse($model->isLeaf());
    }


    public function testElasticSetAndGet(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->title = 'Test Title';
        $model->count = 42;

        $I->assertEquals('Test Title', $model->title);
        $I->assertEquals(42, $model->count);
    }

    public function testElasticIsset(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $I->assertFalse(isset($model->title));

        $model->title = 'Hello';

        $I->assertTrue(isset($model->title));
    }

    public function testElasticCallGetter(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;
        $model->title = 'Getter Test';

        $I->assertEquals('Getter Test', $model->getTitle());
    }

    public function testElasticCallSetter(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->setTitle('Setter Test');

        $I->assertEquals('Setter Test', $model->title);
    }


    public function testBothTraitsWorkTogether(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->mockLeft = 1;
        $model->mockRight = 10;
        $model->mockLevel = 2;
        $model->title = 'Combined Test';
        $model->count = 100;

        $I->assertEquals(1, $model->mockLeft);
        $I->assertEquals(10, $model->mockRight);
        $I->assertEquals(2, $model->mockLevel);
        $I->assertEquals('Combined Test', $model->title);
        $I->assertEquals(100, $model->count);
    }

    public function testBothTraitsIssetWorkTogether(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $I->assertFalse(isset($model->mockLeft));
        $I->assertFalse(isset($model->title));

        $model->mockLeft = 5;
        $model->title = 'Test';

        $I->assertTrue(isset($model->mockLeft));
        $I->assertTrue(isset($model->title));

        $I->assertFalse(isset($model->mockRight));
        $I->assertFalse(isset($model->count));
    }

    public function testBothTraitsCallWorkTogether(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->setMockLeft(1);
        $model->setMockRight(2);
        $model->setTitle('Call Test');
        $model->setCount(50);

        $I->assertEquals(1, $model->getMockLeft());
        $I->assertEquals(2, $model->getMockRight());
        $I->assertEquals('Call Test', $model->getTitle());
        $I->assertEquals(50, $model->getCount());

        $I->assertTrue($model->isLeaf());
    }

    public function testPriorityMockMagicOverElastic(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->mockLeft = 42;
        $I->assertEquals(42, $model->mockLeft);

        $model->title = 'Priority Test';
        $I->assertEquals('Priority Test', $model->title);
    }

    public function testNoConflictBetweenTraits(ElasticCombinedTraitsTester $I): void
    {
        $this->ensureTestData();

        $model = new CombinedTraitsModel();

        $I->assertInstanceOf(CombinedTraitsModel::class, $model);

        $model->mockLeft = 1;
        $I->assertEquals(1, $model->mockLeft);

        $model->elasticSchemaId = $this->schemaId;
        $model->title = 'No Conflict';
        $I->assertEquals('No Conflict', $model->title);
    }
}
