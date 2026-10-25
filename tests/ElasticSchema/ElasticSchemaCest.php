<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\ElasticSchema;

use Blackcube\ActiveRecord\Elastic\ElasticSchema;
use Blackcube\ActiveRecord\Tests\Support\Elastic\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\ElasticSchemaTester;

class ElasticSchemaCest
{
    use DatabaseCestTrait;

    public function testCreateElasticSchema(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('TestSchema');
        $schema->setSchema(json_encode([
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
            ],
        ]));
        $schema->save();

        $I->assertNotNull($schema->getId());
        $I->assertEquals('TestSchema', $schema->getName());
    }

    public function testReadElasticSchema(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('ReadTest');
        $schema->setSchema('{"type":"object"}');
        $schema->setView('custom_view');
        $schema->save();

        $id = $schema->getId();

        $found = ElasticSchema::query()->where(['id' => $id])->one();

        $I->assertNotNull($found);
        $I->assertEquals('ReadTest', $found->getName());
        $I->assertEquals('{"type":"object"}', $found->getSchema());
        $I->assertEquals('custom_view', $found->getView());
        $I->assertNotNull($found->getDateCreate());
    }

    public function testUpdateElasticSchema(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('UpdateTest');
        $schema->setSchema('{"type":"object"}');
        $schema->save();

        $id = $schema->getId();

        $found = ElasticSchema::query()->where(['id' => $id])->one();
        $found->setName('UpdatedName');
        $found->save();

        $updated = ElasticSchema::query()->where(['id' => $id])->one();

        $I->assertEquals('UpdatedName', $updated->getName());
    }

    public function testDeleteElasticSchema(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('DeleteTest');
        $schema->save();

        $id = $schema->getId();

        $found = ElasticSchema::query()->where(['id' => $id])->one();
        $found->delete();

        $deleted = ElasticSchema::query()->where(['id' => $id])->one();

        $I->assertNull($deleted);
    }

    public function testGetDisplayViewWithNullBasePath(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('TestSchema');

        $I->assertFalse($schema->getDisplayView(null));
    }

    public function testGetDisplayViewWithCustomView(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('TestSchema');
        $schema->setView('my_custom_view');

        $result = $schema->getDisplayView('/tmp', true);

        $I->assertEquals('/tmp/my_custom_view.php', $result);
    }

    public function testGetDisplayViewFromName(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('MyTestSchema');

        $result = $schema->getDisplayView('/tmp', true);

        $I->assertEquals('/tmp/my_test_schema.php', $result);
    }

    public function testGetDisplayViewFileNotExists(ElasticSchemaTester $I): void
    {
        $schema = new ElasticSchema();
        $schema->setName('NonExistent');

        $result = $schema->getDisplayView('/tmp');

        $I->assertFalse($result);
    }

    public function testGetDisplayViewFileExists(ElasticSchemaTester $I): void
    {
        $tempDir = sys_get_temp_dir() . '/elastic_test_' . uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir . '/existing_view.php', '<?php // test');

        $schema = new ElasticSchema();
        $schema->setName('ExistingView');

        $result = $schema->getDisplayView($tempDir);

        $I->assertEquals($tempDir . '/existing_view.php', $result);

        unlink($tempDir . '/existing_view.php');
        rmdir($tempDir);
    }
}
