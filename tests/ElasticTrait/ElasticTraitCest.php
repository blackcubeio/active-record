<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticTrait;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ElasticTraitTester;
use Blackcube\ActiveRecord\Tests\Support\Elastic\TestModel;
use Blackcube\ActiveRecord\Tests\Support\Elastic\TestModelWithLabels;
use Blackcube\ActiveRecord\Tests\Support\Elastic\TestModelWithMagicParent;

class ElasticTraitCest
{
    use DatabaseCestTrait;

    private int $schemaId;
    private int $schemaWithMetadataId;

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
            ],
        ]));
        $schema->save();
        $this->schemaId = $schema->getId();

        $schemaWithMetadata = new ElasticSchema();
        $schemaWithMetadata->setName('TestSchemaWithMetadata');
        $schemaWithMetadata->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'subject' => [
                    'type' => 'string',
                    'title' => 'Message subject',
                    'description' => 'Choose a relevant subject',
                    'placeholder' => 'Information request',
                ],
                'message' => [
                    'type' => 'string',
                    'title' => 'Your message',
                    'description' => 'Describe your request in detail',
                    'placeholder' => 'Hello, I would like...',
                ],
                'priority' => [
                    'type' => 'integer',
                    'title' => 'Priority',
                    'placeholder' => '1',
                ],
                'internal' => [
                    'type' => 'boolean',
                ],
            ],
        ]));
        $schemaWithMetadata->save();
        $this->schemaWithMetadataId = $schemaWithMetadata->getId();
    }

    public function testModelWithoutSchemaHasEmptyExtras(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->setTitle('No Schema');

        $extras = $model->getElasticValues();

        $I->assertIsArray($extras);
        $I->assertEmpty($extras);
    }

    public function testModelWithSchemaLoadsAttributes(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->setTitle('With Schema');
        $model->elasticSchemaId = $this->schemaId;

        $extras = $model->getElasticValues();

        $I->assertIsArray($extras);
        $I->assertArrayHasKey('description', $extras);
        $I->assertArrayHasKey('price', $extras);
        $I->assertArrayHasKey('active', $extras);
    }

    public function testSetExtrasOnlyAcceptsSchemaAttributes(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->protectElastic(false);
        $model->_extras = json_encode([
            'description' => 'Test',
            'price' => 19.99,
            'unknown' => 'ignored',
        ]);

        $extras = $model->getElasticValues();

        $I->assertEquals('Test', $extras['description']);
        $I->assertEquals(19.99, $extras['price']);
        $I->assertArrayNotHasKey('unknown', $extras);
    }

    public function testElasticSchemaIdCanBeSet(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();

        $I->assertNull($model->elasticSchemaId);

        $model->elasticSchemaId = $this->schemaId;

        $I->assertEquals($this->schemaId, $model->elasticSchemaId);
    }

    public function testSetSchemaPropertyViaMagicSet(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->description = 'Test description';

        $extras = $model->getElasticValues();
        $I->assertEquals('Test description', $extras['description']);
    }

    public function testSetSchemaPropertyViaSetExtras(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->protectElastic(false);
        $model->set_extras(['description' => 'Via set_extras']);

        $extras = $model->getElasticValues();
        $I->assertEquals('Via set_extras', $extras['description']);
    }

    public function testSetSchemaPropertyViaExtrasArray(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->protectElastic(false);
        $model->_extras = json_encode(['description' => 'Via extras array']);

        $extras = $model->getElasticValues();
        $I->assertEquals('Via extras array', $extras['description']);
    }

    public function testGetSchemaPropertyViaMagicGet(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;
        $model->protectElastic(false);
        $model->_extras = json_encode(['description' => 'Test magic get']);

        $I->assertEquals('Test magic get', $model->description);
    }

    public function testGetSchemaPropertyViaGetterMethod(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;
        $model->protectElastic(false);
        $model->_extras = json_encode(['description' => 'Test getter method']);

        $I->assertEquals('Test getter method', $model->getDescription());
    }

    public function testSetSchemaPropertyViaSetterMethod(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->setDescription('Via setter method');

        $I->assertEquals('Via setter method', $model->description);
        $I->assertEquals('Via setter method', $model->getDescription());
    }

    public function testPropertyAccessIsCaseInsensitive(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->setDeScrIpTiOn('Case insensitive value');

        $I->assertEquals('Case insensitive value', $model->description);
        $I->assertEquals('Case insensitive value', $model->DESCRIPTION);
        $I->assertEquals('Case insensitive value', $model->Description);
        $I->assertEquals('Case insensitive value', $model->getDescription());
        $I->assertEquals('Case insensitive value', $model->getDESCRIPTION());

        $extras = $model->getElasticValues();
        $I->assertArrayHasKey('description', $extras);
        $I->assertEquals('Case insensitive value', $extras['description']);
    }

    public function testSaveModelWithElasticProperties(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->setTitle('Test Save');
        $model->elasticSchemaId = $this->schemaId;
        $model->description = 'Saved description';
        $model->price = 29.99;
        $model->active = true;

        $model->insert();

        $I->assertNotNull($model->getId());

        $extras = $model->getElasticValues();
        $I->assertEquals('Saved description', $extras['description']);
        $I->assertEquals(29.99, $extras['price']);
        $I->assertTrue($extras['active']);
    }

    public function testGetUnknownPropertyThrowsError(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $I->expectThrowable(\Throwable::class, function () use ($model) {
            $value = $model->unknownProperty;
        });
    }

    public function testSetUnknownPropertyThrowsError(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $I->expectThrowable(\Throwable::class, function () use ($model) {
            $model->unknownProperty = 'value';
        });
    }

    public function testCallUnknownMethodThrowsError(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $I->expectThrowable(\Throwable::class, function () use ($model) {
            $model->getUnknownProperty();
        });
    }

    public function testParentMagicGetIsCalled(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModelWithMagicParent();
        $model->elasticSchemaId = $this->schemaId;

        $model->unknownProperty = 'parent value';

        $I->assertEquals('parent value', $model->unknownProperty);
    }

    public function testParentMagicIssetIsCalled(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModelWithMagicParent();
        $model->elasticSchemaId = $this->schemaId;

        $I->assertFalse(isset($model->unknownProperty));

        $model->unknownProperty = 'value';

        $I->assertTrue(isset($model->unknownProperty));
    }

    public function testParentMagicCallIsCalled(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModelWithMagicParent();
        $model->elasticSchemaId = $this->schemaId;

        $I->assertEquals('magic_Test', $model->getMagicTest());
    }

    public function testLoadModelWithElasticProperties(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->setTitle('Test Load');
        $model->elasticSchemaId = $this->schemaId;
        $model->description = 'Loaded description';
        $model->price = 49.99;
        $model->active = false;
        $model->insert();

        $savedId = $model->getId();

        $loaded = TestModel::query()->where(['id' => $savedId])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('Test Load', $loaded->getTitle());
        $I->assertEquals($this->schemaId, $loaded->elasticSchemaId);
        $I->assertEquals('Loaded description', $loaded->description);
        $I->assertEquals(49.99, $loaded->price);
        $I->assertFalse($loaded->active);
    }

    public function testSetExtrasColumnThrowsWhenProtected(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $I->expectThrowable(\Error::class, function () use ($model) {
            $model->_extras = json_encode(['description' => 'Should fail']);
        });
    }

    public function testProtectElasticAllowsWriteWhenDisabled(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $model->protectElastic(false);
        $model->_extras = json_encode(['description' => 'Should work']);

        $I->assertEquals('Should work', $model->description);
    }

    public function testGetElasticLabelsReturnsEmptyWithoutSchema(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();

        $labels = $model->getElasticLabels();

        $I->assertIsArray($labels);
        $I->assertEmpty($labels);
    }

    public function testGetElasticLabelsReturnsEmptyForSchemaWithoutTitles(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $labels = $model->getElasticLabels();

        $I->assertIsArray($labels);
        $I->assertEmpty($labels);
    }

    public function testGetElasticLabelsReturnsTitlesFromSchema(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $labels = $model->getElasticLabels();

        $I->assertIsArray($labels);
        $I->assertArrayHasKey('subject', $labels);
        $I->assertArrayHasKey('message', $labels);
        $I->assertArrayHasKey('priority', $labels);
        $I->assertArrayNotHasKey('internal', $labels);

        $I->assertEquals('Message subject', $labels['subject']);
        $I->assertEquals('Your message', $labels['message']);
        $I->assertEquals('Priority', $labels['priority']);
    }

    public function testGetElasticHintsReturnsEmptyWithoutSchema(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();

        $hints = $model->getElasticHints();

        $I->assertIsArray($hints);
        $I->assertEmpty($hints);
    }

    public function testGetElasticHintsReturnsEmptyForSchemaWithoutDescriptions(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $hints = $model->getElasticHints();

        $I->assertIsArray($hints);
        $I->assertEmpty($hints);
    }

    public function testGetElasticHintsReturnsDescriptionsFromSchema(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $hints = $model->getElasticHints();

        $I->assertIsArray($hints);
        $I->assertArrayHasKey('subject', $hints);
        $I->assertArrayHasKey('message', $hints);
        $I->assertArrayNotHasKey('priority', $hints);
        $I->assertArrayNotHasKey('internal', $hints);

        $I->assertEquals('Choose a relevant subject', $hints['subject']);
        $I->assertEquals('Describe your request in detail', $hints['message']);
    }

    public function testGetElasticPlaceholdersReturnsEmptyWithoutSchema(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();

        $placeholders = $model->getElasticPlaceholders();

        $I->assertIsArray($placeholders);
        $I->assertEmpty($placeholders);
    }

    public function testGetElasticPlaceholdersReturnsEmptyForSchemaWithoutPlaceholder(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaId;

        $placeholders = $model->getElasticPlaceholders();

        $I->assertIsArray($placeholders);
        $I->assertEmpty($placeholders);
    }

    public function testGetElasticPlaceholdersReturnsFirstExampleFromSchema(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $placeholders = $model->getElasticPlaceholders();

        $I->assertIsArray($placeholders);
        $I->assertArrayHasKey('subject', $placeholders);
        $I->assertArrayHasKey('message', $placeholders);
        $I->assertArrayHasKey('priority', $placeholders);
        $I->assertArrayNotHasKey('internal', $placeholders);

        $I->assertEquals('Information request', $placeholders['subject']);
        $I->assertEquals('Hello, I would like...', $placeholders['message']);
        $I->assertEquals('1', $placeholders['priority']);
    }

    public function testGetPropertyLabelReturnsElasticLabel(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Message subject', $model->getPropertyLabel('subject'));
        $I->assertEquals('Your message', $model->getPropertyLabel('message'));
        $I->assertEquals('Priority', $model->getPropertyLabel('priority'));
    }

    public function testGetPropertyLabelFallsBackToGenerated(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Internal', $model->getPropertyLabel('internal'));
    }

    public function testGetPropertyHintReturnsElasticHint(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Choose a relevant subject', $model->getPropertyHint('subject'));
        $I->assertEquals('Describe your request in detail', $model->getPropertyHint('message'));
    }

    public function testGetPropertyHintReturnsEmptyForMissingHint(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('', $model->getPropertyHint('priority'));
    }

    public function testGetPropertyPlaceholderReturnsElasticPlaceholder(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Information request', $model->getPropertyPlaceholder('subject'));
        $I->assertEquals('Hello, I would like...', $model->getPropertyPlaceholder('message'));
        $I->assertEquals('1', $model->getPropertyPlaceholder('priority'));
    }

    public function testGetPropertyPlaceholderReturnsEmptyForMissingPlaceholder(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModel();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('', $model->getPropertyPlaceholder('internal'));
    }


    public function testLoadModelWithFileFormatProperty(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $fileSchema = new ElasticSchema();
        $fileSchema->setName('FileTestSchema');
        $fileSchema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'lang' => [
                    'type' => 'string',
                    'title' => 'Related content',
                ],
                'surtitle' => [
                    'type' => 'string',
                    'title' => 'Surtitle',
                ],
                'title' => [
                    'type' => 'string',
                    'title' => 'Title',
                ],
                'description' => [
                    'type' => 'string',
                    'format' => 'wysiwyg',
                    'title' => 'Description',
                ],
                'image' => [
                    'type' => 'string',
                    'format' => 'file',
                    'fileType' => 'png,jpg,svg',
                    'title' => 'Image',
                ],
                'imageAlt' => [
                    'type' => 'string',
                    'title' => 'Alternate',
                ],
                'cta' => [
                    'type' => 'string',
                    'title' => 'Cta',
                ],
                'ctaRoute' => [
                    'type' => 'string',
                    'title' => 'Target',
                ],
                'ctaUrl' => [
                    'type' => 'string',
                    'title' => 'Target (URL)',
                ],
            ],
            'required' => [
                'title',
                'description',
            ],
        ]));
        $fileSchema->save();
        $fileSchemaId = $fileSchema->getId();

        $extrasData = json_encode([
            'lang' => 'azertyu yolo2',
            'surtitle' => '',
            'title' => 'wxcvbn,n',
            'description' => 'azertyu',
            'image' => '@blfs/tags/3/blocs/16/bcr.png',
            'imageAlt' => '',
            'cta' => '',
            'ctaRoute' => '',
            'ctaUrl' => 'qsdfghj',
        ]);

        $model = new TestModel();
        $model->setTitle('File Test');
        $model->elasticSchemaId = $fileSchemaId;
        $model->protectElastic(false);
        $model->_extras = $extrasData;
        $model->insert();

        $savedId = $model->getId();

        TestModel::clearSchemaCache();
        $loaded = TestModel::query()->where(['id' => $savedId])->one();

        $I->assertNotNull($loaded);
        $I->assertSame('@blfs/tags/3/blocs/16/bcr.png', $loaded->image);
        $I->assertSame('wxcvbn,n', $loaded->title);
        $I->assertSame('azertyu yolo2', $loaded->lang);
        $I->assertSame('qsdfghj', $loaded->ctaUrl);

        $values = $loaded->getElasticValues();
        $I->assertSame('@blfs/tags/3/blocs/16/bcr.png', $values['image']);
    }


    public function testModelLabelTakesPriorityOverElasticLabel(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModelWithLabels();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Model-defined label', $model->getPropertyLabel('subject'));

        $I->assertEquals('Your message', $model->getPropertyLabel('message'));

        $I->assertEquals('Internal', $model->getPropertyLabel('internal'));
    }

    public function testModelHintTakesPriorityOverElasticHint(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModelWithLabels();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Model-defined hint', $model->getPropertyHint('subject'));

        $I->assertEquals('Describe your request in detail', $model->getPropertyHint('message'));

        $I->assertEquals('', $model->getPropertyHint('priority'));
    }

    public function testModelPlaceholderTakesPriorityOverElasticPlaceholder(ElasticTraitTester $I): void
    {
        $this->ensureTestData();

        $model = new TestModelWithLabels();
        $model->elasticSchemaId = $this->schemaWithMetadataId;

        $I->assertEquals('Model-defined placeholder', $model->getPropertyPlaceholder('subject'));

        $I->assertEquals('Hello, I would like...', $model->getPropertyPlaceholder('message'));

        $I->assertEquals('', $model->getPropertyPlaceholder('internal'));
    }
}
