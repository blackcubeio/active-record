<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticIntegration;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ElasticIntegrationTester;
use Blackcube\ActiveRecord\Tests\Support\Elastic\Tag;
use Blackcube\ActiveRecord\Elastic\Validator\ElasticRulesManager;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Validator\Validator;

class TagElasticCest
{
    use DatabaseCestTrait;

    private int $schemaId;
    private ElasticRulesManager $rulesManager;

    private function ensureTestData(): void
    {
        $schema = new ElasticSchema();
        $schema->setName('tagging');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'color' => [
                    'type' => 'string',
                    'pattern' => '^#[0-9a-fA-F]{6}$',
                ],
                'description' => [
                    'type' => 'string',
                ],
            ],
            'required' => ['color'],
        ]));
        $schema->save();
        $this->schemaId = $schema->getId();

        $config = ContainerConfig::create()
            ->withDefinitions([
                ElasticRulesManager::class => ElasticRulesManager::class,
            ]);
        $container = new Container($config);
        $this->rulesManager = $container->get(ElasticRulesManager::class);
    }


    public function testTagsTableExists(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify that the tags table was created by migration');

        $db = ConnectionProvider::get();
        $tableSchema = $db->getTableSchema('{{%tags}}');

        $I->assertNotNull($tableSchema);
        $I->assertNotNull($tableSchema->getColumn('id'));
        $I->assertNotNull($tableSchema->getColumn('name'));
        $I->assertNotNull($tableSchema->getColumn('elasticSchemaId'));
        $I->assertNotNull($tableSchema->getColumn('_extras'));
    }

    public function testElasticSchemaWasCreated(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify that the tagging schema was created');

        $schema = ElasticSchema::query()->where(['name' => 'tagging'])->one();

        $I->assertNotNull($schema);
        $I->assertEquals('tagging', $schema->getName());

        $decoded = json_decode($schema->getSchema(), true);
        $I->assertArrayHasKey('properties', $decoded);
        $I->assertArrayHasKey('color', $decoded['properties']);
        $I->assertArrayHasKey('description', $decoded['properties']);
        $I->assertContains('color', $decoded['required']);
    }


    public function testCreateTagWithoutElastic(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create a tag without elastic properties');

        $tag = new Tag();
        $tag->setName('Simple Tag');
        $tag->insert();

        $I->assertNotNull($tag->getId());

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $I->assertNotNull($loaded);
        $I->assertEquals('Simple Tag', $loaded->getName());
    }

    public function testCreateTagWithElasticProperties(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create a tag with elastic properties');

        $tag = new Tag();
        $tag->setName('Colored Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = '#ff5733';
        $tag->description = 'A beautiful orange color';
        $tag->insert();

        $I->assertNotNull($tag->getId());

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $I->assertNotNull($loaded);
        $I->assertEquals('Colored Tag', $loaded->getName());
        $I->assertEquals($this->schemaId, $loaded->elasticSchemaId);
        $I->assertEquals('#ff5733', $loaded->color);
        $I->assertEquals('A beautiful orange color', $loaded->description);
    }

    public function testCreateTagWithOnlyRequiredElasticProperty(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create a tag with only required elastic property (color)');

        $tag = new Tag();
        $tag->setName('Minimal Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = '#00ff00';
        $tag->insert();

        $I->assertNotNull($tag->getId());

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $I->assertEquals('#00ff00', $loaded->color);
        $I->assertNull($loaded->description);
    }


    public function testValidationPassesWithValidColor(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation passes with valid color format');

        $tag = new Tag();
        $tag->setName('Valid Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = '#abc123';
        $tag->insert();

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        if ($result->isValid() === false) {
            foreach ($result->getErrors() as $error) {
                codecept_debug('Error: ' . $error->getMessage());
            }
        }

        $I->assertTrue($result->isValid());
    }

    public function testValidationFailsWithInvalidColorFormat(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails with invalid color format');

        $tag = new Tag();
        $tag->setName('Invalid Color Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = 'not-a-color';
        $tag->insert();

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }

    public function testValidationFailsWithMissingRequiredColor(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when required color is missing');

        $tag = new Tag();
        $tag->setName('No Color Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->insert();

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }

    public function testValidationFailsWithEmptyColor(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when required color is empty string');

        $tag = new Tag();
        $tag->setName('Empty Color Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = '';
        $tag->insert();

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }


    public function testUpdateElasticProperty(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('update an elastic property and verify persistence');

        $tag = new Tag();
        $tag->setName('Updatable Tag');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = '#111111';
        $tag->insert();

        $tagId = $tag->getId();

        $loaded = Tag::query()->where(['id' => $tagId])->one();
        $loaded->color = '#222222';
        $loaded->description = 'Updated description';
        $loaded->update();

        $reloaded = Tag::query()->where(['id' => $tagId])->one();
        $I->assertEquals('#222222', $reloaded->color);
        $I->assertEquals('Updated description', $reloaded->description);
    }

    public function testUpdateElasticPropertyToInvalidValueFailsValidation(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails after updating to invalid value');

        $tag = new Tag();
        $tag->setName('Will Be Invalid');
        $tag->elasticSchemaId = $this->schemaId;
        $tag->color = '#ffffff';
        $tag->insert();

        $loaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $loaded->color = 'invalid';
        $loaded->update();

        $reloaded = Tag::query()->where(['id' => $tag->getId()])->one();
        $rules = $this->rulesManager->getRules($reloaded);
        $validator = new Validator();
        $result = $validator->validate($reloaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }


    public function testQueryAllTags(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query all tags and verify elastic properties');

        $tag1 = new Tag();
        $tag1->setName('Red Tag');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#ff0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Green Tag');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#00ff00';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Blue Tag');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#0000ff';
        $tag3->description = 'Blue is cool';
        $tag3->insert();

        $tags = Tag::query()->all();

        $I->assertCount(3, $tags);

        $colors = [];
        foreach ($tags as $tag) {
            $colors[] = $tag->color;
        }

        $I->assertContains('#ff0000', $colors);
        $I->assertContains('#00ff00', $colors);
        $I->assertContains('#0000ff', $colors);
    }


    public function testWhereWithVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags using where on virtual column (color)');

        $tag1 = new Tag();
        $tag1->setName('Red Tag');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#ff0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Green Tag');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#00ff00';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Another Red Tag');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#ff0000';
        $tag3->insert();

        $redTags = Tag::query()->where(['color' => '#ff0000'])->all();

        $I->assertCount(2, $redTags);
        foreach ($redTags as $tag) {
            $I->assertEquals('#ff0000', $tag->color);
        }
    }

    public function testAndWhereWithVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags using andWhere on virtual column');

        $tag1 = new Tag();
        $tag1->setName('Red Tag');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#ff0000';
        $tag1->description = 'Primary color';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Green Tag');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#00ff00';
        $tag2->description = 'Primary color';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Dark Red Tag');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#ff0000';
        $tag3->description = 'Dark shade';
        $tag3->insert();

        $tags = Tag::query()
            ->where(['color' => '#ff0000'])
            ->andWhere(['description' => 'Primary color'])
            ->all();

        $I->assertCount(1, $tags);
        $I->assertEquals('Red Tag', $tags[0]->getName());
    }

    public function testOrWhereWithVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags using orWhere on virtual column');

        $tag1 = new Tag();
        $tag1->setName('Red Tag');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#ff0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Green Tag');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#00ff00';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Blue Tag');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#0000ff';
        $tag3->insert();

        $tags = Tag::query()
            ->where(['color' => '#ff0000'])
            ->orWhere(['color' => '#0000ff'])
            ->all();

        $I->assertCount(2, $tags);
        $colors = array_map(fn($t) => $t->color, $tags);
        $I->assertContains('#ff0000', $colors);
        $I->assertContains('#0000ff', $colors);
    }

    public function testOrderByVirtualColumnAsc(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags ordered by virtual column ascending');

        $tag1 = new Tag();
        $tag1->setName('Tag C');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#cc0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Tag A');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#aa0000';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Tag B');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#bb0000';
        $tag3->insert();

        $tags = Tag::query()->orderBy(['color' => SORT_ASC])->all();

        $I->assertCount(3, $tags);
        $I->assertEquals('#aa0000', $tags[0]->color);
        $I->assertEquals('#bb0000', $tags[1]->color);
        $I->assertEquals('#cc0000', $tags[2]->color);
    }

    public function testOrderByVirtualColumnDesc(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags ordered by virtual column descending');

        $tag1 = new Tag();
        $tag1->setName('Tag C');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#cc0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Tag A');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#aa0000';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Tag B');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#bb0000';
        $tag3->insert();

        $tags = Tag::query()->orderBy(['color' => SORT_DESC])->all();

        $I->assertCount(3, $tags);
        $I->assertEquals('#cc0000', $tags[0]->color);
        $I->assertEquals('#bb0000', $tags[1]->color);
        $I->assertEquals('#aa0000', $tags[2]->color);
    }

    public function testWhereWithMixedColumns(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags using where with both real and virtual columns');

        $tag1 = new Tag();
        $tag1->setName('Red Tag');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#ff0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Green Tag');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#00ff00';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Red Tag');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#00ff00';
        $tag3->insert();

        $tags = Tag::query()
            ->where([
                'name' => 'Red Tag',
                'color' => '#ff0000',
            ])
            ->all();

        $I->assertCount(1, $tags);
        $I->assertEquals('Red Tag', $tags[0]->getName());
        $I->assertEquals('#ff0000', $tags[0]->color);
    }

    public function testOrderByMixedColumns(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags ordered by both real and virtual columns');

        $tag1 = new Tag();
        $tag1->setName('Alpha');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#bb0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Beta');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#aa0000';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Alpha');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#aa0000';
        $tag3->insert();

        $tags = Tag::query()->orderBy([
            'name' => SORT_ASC,
            'color' => SORT_ASC,
        ])->all();

        $I->assertCount(3, $tags);
        $I->assertEquals('Alpha', $tags[0]->getName());
        $I->assertEquals('#aa0000', $tags[0]->color);
        $I->assertEquals('Alpha', $tags[1]->getName());
        $I->assertEquals('#bb0000', $tags[1]->color);
        $I->assertEquals('Beta', $tags[2]->getName());
    }

    public function testWhereLikeWithVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags using LIKE operator on virtual column');

        $tag1 = new Tag();
        $tag1->setName('Tag 1');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#ff0000';
        $tag1->description = 'A beautiful red color';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Tag 2');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#00ff00';
        $tag2->description = 'A beautiful green color';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Tag 3');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#0000ff';
        $tag3->description = 'Just blue';
        $tag3->insert();

        $tags = Tag::query()
            ->where([
                'like',
                'description',
                'beautiful',
            ])
            ->all();

        $I->assertCount(2, $tags);
    }

    public function testWhereComparisonWithVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query tags using comparison operators on virtual column');

        $tag1 = new Tag();
        $tag1->setName('Tag A');
        $tag1->elasticSchemaId = $this->schemaId;
        $tag1->color = '#aa0000';
        $tag1->insert();

        $tag2 = new Tag();
        $tag2->setName('Tag B');
        $tag2->elasticSchemaId = $this->schemaId;
        $tag2->color = '#bb0000';
        $tag2->insert();

        $tag3 = new Tag();
        $tag3->setName('Tag C');
        $tag3->elasticSchemaId = $this->schemaId;
        $tag3->color = '#cc0000';
        $tag3->insert();

        $tags = Tag::query()
            ->where([
                '>',
                'color',
                '#aa0000',
            ])
            ->all();

        $I->assertCount(2, $tags);
        $colors = array_map(fn($t) => $t->color, $tags);
        $I->assertContains('#bb0000', $colors);
        $I->assertContains('#cc0000', $colors);
    }


    public function testColorPatternValidation(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify various color pattern validations');

        $tag = new Tag();
        $tag->setName('Pattern Test');
        $tag->elasticSchemaId = $this->schemaId;

        $validator = new Validator();

        $validColors = [
            '#000000',
            '#ffffff',
            '#AABBCC',
            '#123abc',
            '#AbCdEf',
        ];
        foreach ($validColors as $color) {
            $tag->color = $color;
            $rules = $this->rulesManager->getRules($tag);
            $result = $validator->validate($tag->getElasticValues(), $rules);
            $I->assertTrue($result->isValid(), 'Color ' . $color . ' should be valid');
        }

        $invalidColors = [
            '#fff',
            '#gggggg',
            'ffffff',
            '#1234567',
            'red',
            '#12345',
        ];
        foreach ($invalidColors as $color) {
            $tag->color = $color;
            $rules = $this->rulesManager->getRules($tag);
            $result = $validator->validate($tag->getElasticValues(), $rules);
            $I->assertFalse($result->isValid(), 'Color ' . $color . ' should be invalid');
        }
    }
}
