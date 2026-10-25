<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticIntegration;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\Article;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ElasticIntegrationTester;
use Blackcube\ActiveRecord\Elastic\Validator\ElasticRulesManager;
use Yiisoft\Di\Container;
use Yiisoft\Di\ContainerConfig;
use Yiisoft\Validator\Validator;

/**
 * Integration tests for ElasticTrait with custom column name (_data instead of _extras).
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ArticleElasticCest
{
    use DatabaseCestTrait;

    private int $schemaId;
    private ElasticRulesManager $rulesManager;

    private function ensureTestData(): void
    {
        $schema = new ElasticSchema();
        $schema->setName('article-meta');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'author' => [
                    'type' => 'string',
                    'minLength' => 2,
                ],
                'rating' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 5,
                ],
            ],
            'required' => ['author'],
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


    public function testCreateArticleWithElasticProperties(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create an article with elastic properties using _data column');

        $article = new Article();
        $article->setTitle('Test Article');
        $article->elasticSchemaId = $this->schemaId;
        $article->author = 'John Doe';
        $article->rating = 4;

        $article->insert();

        $loaded = Article::query()->where(['id' => $article->getId()])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('Test Article', $loaded->getTitle());
        $I->assertEquals('John Doe', $loaded->author);
        $I->assertEquals(4, $loaded->rating);
    }

    public function testCreateArticleWithOnlyRequiredProperty(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('create an article with only required elastic property');

        $article = new Article();
        $article->setTitle('Minimal Article');
        $article->elasticSchemaId = $this->schemaId;
        $article->author = 'Jane Doe';

        $article->insert();

        $loaded = Article::query()->where(['id' => $article->getId()])->one();

        $I->assertNotNull($loaded);
        $I->assertEquals('Jane Doe', $loaded->author);
        $I->assertNull($loaded->rating);
    }

    public function testUpdateElasticProperty(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('update an elastic property and verify persistence');

        $article = new Article();
        $article->setTitle('Update Test');
        $article->elasticSchemaId = $this->schemaId;
        $article->author = 'Original Author';
        $article->rating = 3;
        $article->insert();

        $loaded = Article::query()->where(['id' => $article->getId()])->one();
        $loaded->author = 'Updated Author';
        $loaded->rating = 5;
        $loaded->update();

        $reloaded = Article::query()->where(['id' => $article->getId()])->one();
        $I->assertEquals('Updated Author', $reloaded->author);
        $I->assertEquals(5, $reloaded->rating);
    }


    public function testValidationPassesWithValidData(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation passes with valid elastic data');

        $article = new Article();
        $article->setTitle('Valid Article');
        $article->elasticSchemaId = $this->schemaId;
        $article->author = 'Valid Author';
        $article->rating = 5;
        $article->insert();

        $loaded = Article::query()->where(['id' => $article->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertTrue($result->isValid());
    }

    public function testValidationFailsWithInvalidRating(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when rating is out of range');

        $article = new Article();
        $article->setTitle('Invalid Rating Article');
        $article->elasticSchemaId = $this->schemaId;
        $article->author = 'Author';
        $article->rating = 10;
        $article->insert();

        $loaded = Article::query()->where(['id' => $article->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }

    public function testValidationFailsWithMissingRequiredAuthor(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when required author is missing');

        $article = new Article();
        $article->setTitle('No Author Article');
        $article->elasticSchemaId = $this->schemaId;
        $article->insert();

        $loaded = Article::query()->where(['id' => $article->getId()])->one();
        $rules = $this->rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
    }


    public function testQueryWhereOnVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query articles using where on virtual column (author)');

        $article1 = new Article();
        $article1->setTitle('Article 1');
        $article1->elasticSchemaId = $this->schemaId;
        $article1->author = 'Alice';
        $article1->insert();

        $article2 = new Article();
        $article2->setTitle('Article 2');
        $article2->elasticSchemaId = $this->schemaId;
        $article2->author = 'Bob';
        $article2->insert();

        $results = Article::query()->where(['author' => 'Alice'])->all();

        $I->assertCount(1, $results);
        $I->assertEquals('Alice', $results[0]->author);
    }

    public function testQueryOrderByVirtualColumn(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('query articles ordered by virtual column (rating)');

        $article1 = new Article();
        $article1->setTitle('Low Rating');
        $article1->elasticSchemaId = $this->schemaId;
        $article1->author = 'Author 1';
        $article1->rating = 2;
        $article1->insert();

        $article2 = new Article();
        $article2->setTitle('High Rating');
        $article2->elasticSchemaId = $this->schemaId;
        $article2->author = 'Author 2';
        $article2->rating = 5;
        $article2->insert();

        $results = Article::query()->orderBy(['rating' => SORT_DESC])->all();

        $I->assertCount(2, $results);
        $I->assertEquals(5, $results[0]->rating);
        $I->assertEquals(2, $results[1]->rating);
    }


    public function testElasticColumnNameIsData(ElasticIntegrationTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify that the elastic column name is _data');

        $article = new Article();

        $I->assertEquals('_data', $article->elasticColumn());
    }
}
