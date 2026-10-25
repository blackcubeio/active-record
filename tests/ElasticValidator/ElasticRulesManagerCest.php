<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticValidator;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\Elastic\TestModel;
use Blackcube\ActiveRecord\Tests\Support\ElasticValidatorTester;
use Blackcube\ActiveRecord\Elastic\Validator\ElasticRulesManager;
use Yiisoft\Validator\Validator;

/**
 * Tests for ElasticRulesManager: validation rules built from the JSON Schema of an elastic model.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ElasticRulesManagerCest
{
    use DatabaseCestTrait;

    private int $schemaId;

    private function ensureTestData(): void
    {
        $schema = new ElasticSchema();
        $schema->setName('TestSchemaWithRequired');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                ],
                'age' => ['type' => 'integer'],
            ],
            'required' => [
                'name',
                'email',
            ],
        ]));
        $schema->save();
        $this->schemaId = $schema->getId();
    }

    public function testValidationPassesWithRequiredFieldsFilled(ElasticValidatorTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation passes when required fields are filled');

        $model = new TestModel();
        $model->setTitle('Test');
        $model->elasticSchemaId = $this->schemaId;
        $model->name = 'John Doe';
        $model->email = 'john@example.com';
        $model->age = 30;
        $model->insert();

        $loaded = TestModel::query()->andWhere(['id' => $model->getId()])->one();

        $rulesManager = new ElasticRulesManager();
        $rules = $rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertTrue($result->isValid());
    }

    public function testValidationFailsWhenRequiredFieldIsNull(ElasticValidatorTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when required field is set to null');

        $model = new TestModel();
        $model->setTitle('Test');
        $model->elasticSchemaId = $this->schemaId;
        $model->name = 'John Doe';
        $model->email = 'john@example.com';
        $model->insert();

        $loaded = TestModel::query()->andWhere(['id' => $model->getId()])->one();

        $loaded->name = null;

        $rulesManager = new ElasticRulesManager();
        $rules = $rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
        $I->assertNotEmpty($result->getErrors());
    }

    public function testValidationFailsWhenRequiredFieldIsEmptyString(ElasticValidatorTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when required field is set to empty string');

        $model = new TestModel();
        $model->setTitle('Test');
        $model->elasticSchemaId = $this->schemaId;
        $model->name = 'John Doe';
        $model->email = 'john@example.com';
        $model->insert();

        $loaded = TestModel::query()->andWhere(['id' => $model->getId()])->one();

        $loaded->name = '';

        $rulesManager = new ElasticRulesManager();
        $rules = $rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
        $I->assertNotEmpty($result->getErrors());
    }

    public function testValidationFailsWhenEmailFormatIsInvalid(ElasticValidatorTester $I): void
    {
        $this->ensureTestData();

        $I->wantTo('verify validation fails when email format is invalid');

        $model = new TestModel();
        $model->setTitle('Test');
        $model->elasticSchemaId = $this->schemaId;
        $model->name = 'John Doe';
        $model->email = 'not-an-email';
        $model->insert();

        $loaded = TestModel::query()->andWhere(['id' => $model->getId()])->one();

        $rulesManager = new ElasticRulesManager();
        $rules = $rulesManager->getRules($loaded);
        $validator = new Validator();
        $result = $validator->validate($loaded->getElasticValues(), $rules);

        $I->assertFalse($result->isValid());
        $I->assertNotEmpty($result->getErrors());
    }
}
