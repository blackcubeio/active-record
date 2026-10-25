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
use Blackcube\ActiveRecord\Tests\Support\Elastic\Product;
use Blackcube\ActiveRecord\Elastic\Validator\ElasticRulesManager;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Validator\Validator;

/**
 * Integration tests for ElasticTrait with fully custom column names:
 * - 'data' instead of '_extras'
 * - 'sid' instead of 'elasticSchemaId'
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ProductElasticCest
{
    use DatabaseCestTrait;

    private int $schemaId;
    private ElasticRulesManager $rulesManager;

    private function ensureTestData(): void
    {
        $schema = new ElasticSchema();
        $schema->setName('product-info');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'sku' => [
                    'type' => 'string',
                    'minLength' => 3,
                    'maxLength' => 20,
                ],
                'price' => [
                    'type' => 'number',
                    'minimum' => 0,
                ],
                'inStock' => [
                    'type' => 'boolean',
                ],
            ],
            'required' => ['sku'],
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


    public function testElasticColumnNameIsData(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify that the elastic column name is data');

        $product = new Product();

        $I->assertEquals('data', $product->elasticColumn());
    }

    public function testElasticSchemaColumnNameIsSid(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify that the schema column name is sid');

        $product = new Product();

        $I->assertEquals('sid', $product->elasticSchemaColumn());
    }


    public function testCreateProductWithElasticProperties(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create a product with elastic properties using custom column names');

        $product = new Product();
        $product->setName('Test Product');
        $product->sid = $this->schemaId;
        $product->sku = 'PROD-001';
        $product->price = 29.99;
        $product->inStock = true;

        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('Test Product', $loaded->getName());
        $I->assertEquals($this->schemaId, $loaded->sid);
        $I->assertEquals('PROD-001', $loaded->sku);
        $I->assertEquals(29.99, $loaded->price);
        $I->assertTrue($loaded->inStock);
    }

    public function testCreateProductWithOnlyRequiredProperty(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create a product with only required elastic property');

        $product = new Product();
        $product->setName('Minimal Product');
        $product->sid = $this->schemaId;
        $product->sku = 'MIN-001';

        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('MIN-001', $loaded->sku);
        $I->assertNull($loaded->price);
        $I->assertNull($loaded->inStock);
    }

    public function testUpdateElasticProperty(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('update an elastic property and verify persistence');

        $product = new Product();
        $product->setName('Update Test');
        $product->sid = $this->schemaId;
        $product->sku = 'UPD-001';
        $product->price = 19.99;
        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();
        $loaded->sku = 'UPD-002';
        $loaded->price = 24.99;
        $loaded->inStock = false;
        $loaded->update();

        $reloaded = Product::query()->where(['id' => $product->getId()])->one();
        $I->assertEquals('UPD-002', $reloaded->sku);
        $I->assertEquals(24.99, $reloaded->price);
        $I->assertFalse($reloaded->inStock);
    }


    public function testGetDataReturnsJsonString(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify getData() returns JSON string');

        $product = new Product();
        $product->sid = $this->schemaId;
        $product->sku = 'JSON-001';
        $product->price = 9.99;

        $data = $product->getData();

        $I->assertIsString($data);
        $decoded = json_decode($data, true);
        $I->assertEquals('JSON-001', $decoded['sku']);
        $I->assertEquals(9.99, $decoded['price']);
    }

    public function testSetDataAcceptsJsonString(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify setData() accepts JSON string with protection disabled');

        $product = new Product();
        $product->sid = $this->schemaId;
        $product->protectElastic(false);
        $product->setData(json_encode([
            'sku' => 'SET-001',
            'price' => 14.99,
        ]));

        $I->assertEquals('SET-001', $product->sku);
        $I->assertEquals(14.99, $product->price);
    }

    public function testGetSidReturnSchemaId(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify getSid() returns schema ID');

        $product = new Product();
        $product->sid = $this->schemaId;

        $I->assertEquals($this->schemaId, $product->getSid());
    }

    public function testSetSidSetsSchemaId(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify setSid() sets schema ID');

        $product = new Product();
        $product->setSid($this->schemaId);

        $I->assertEquals($this->schemaId, $product->sid);
    }


    public function testValidationPassesWithValidData(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation passes with valid elastic data');

        $product = new Product();
        $product->setName('Valid Product');
        $product->sid = $this->schemaId;
        $product->sku = 'VAL-001';
        $product->price = 49.99;
        $product->inStock = true;
        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertTrue($result->isValid());
    }

    public function testValidationFailsWithSkuTooShort(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when sku is too short');

        $product = new Product();
        $product->setName('Invalid SKU Product');
        $product->sid = $this->schemaId;
        $product->sku = 'AB';
        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }

    public function testValidationFailsWithNegativePrice(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when price is negative');

        $product = new Product();
        $product->setName('Negative Price Product');
        $product->sid = $this->schemaId;
        $product->sku = 'NEG-001';
        $product->price = -10.00;
        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }

    public function testValidationFailsWithMissingRequiredSku(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when required sku is missing');

        $product = new Product();
        $product->setName('No SKU Product');
        $product->sid = $this->schemaId;
        $product->insert();

        $loaded = Product::query()->where(['id' => $product->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }


    public function testQueryWhereOnVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query products using where on virtual column (sku)');

        $product1 = new Product();
        $product1->setName('Product 1');
        $product1->sid = $this->schemaId;
        $product1->sku = 'SKU-AAA';
        $product1->insert();

        $product2 = new Product();
        $product2->setName('Product 2');
        $product2->sid = $this->schemaId;
        $product2->sku = 'SKU-BBB';
        $product2->insert();

        $results = Product::query()->where(['sku' => 'SKU-AAA'])->all();

        $I->assertCount(1, $results);
        $I->assertEquals('SKU-AAA', $results[0]->sku);
    }

    public function testQueryOrderByVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query products ordered by virtual column (price)');

        $product1 = new Product();
        $product1->setName('Cheap Product');
        $product1->sid = $this->schemaId;
        $product1->sku = 'CHEAP-001';
        $product1->price = 9.99;
        $product1->insert();

        $product2 = new Product();
        $product2->setName('Expensive Product');
        $product2->sid = $this->schemaId;
        $product2->sku = 'EXP-001';
        $product2->price = 99.99;
        $product2->insert();

        $results = Product::query()->orderBy(['price' => SORT_DESC])->all();

        $I->assertCount(2, $results);
        $I->assertEquals(99.99, $results[0]->price);
        $I->assertEquals(9.99, $results[1]->price);
    }

    public function testQueryWithBooleanVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query products filtering by boolean virtual column (inStock)');

        $product1 = new Product();
        $product1->setName('In Stock Product');
        $product1->sid = $this->schemaId;
        $product1->sku = 'STOCK-001';
        $product1->inStock = true;
        $product1->insert();

        $product2 = new Product();
        $product2->setName('Out of Stock Product');
        $product2->sid = $this->schemaId;
        $product2->sku = 'STOCK-002';
        $product2->inStock = false;
        $product2->insert();

        $inStockResults = Product::query()->where(['inStock' => true])->all();
        $outOfStockResults = Product::query()->where(['inStock' => false])->all();

        $I->assertCount(1, $inStockResults);
        $I->assertEquals('STOCK-001', $inStockResults[0]->sku);

        $I->assertCount(1, $outOfStockResults);
        $I->assertEquals('STOCK-002', $outOfStockResults[0]->sku);
    }
}
