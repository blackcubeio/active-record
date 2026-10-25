<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\HazeltreeCombinedTraits;

use Blackcube\ActiveRecord\Tests\Support\Hazeltree\CombinedTraitsModel;
use Blackcube\ActiveRecord\Tests\Support\HazeltreeCombinedTraitsTester;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\DatabaseCestTrait;

/**
 * Integration tests for combining HazeltreeTrait with another trait that has magic methods.
 * This validates the chainable methods pattern for trait composition.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class CombinedTraitsCest
{
    use DatabaseCestTrait;


    public function testHazeltreeSetAndGet(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Test');
        $model->save();

        $I->assertEquals('1', $model->path);
        $I->assertEquals(1, $model->level);
        $I->assertNotNull($model->left);
        $I->assertNotNull($model->right);
    }

    public function testHazeltreeIsset(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Test');
        $model->save();

        $I->assertTrue(isset($model->path));
        $I->assertTrue(isset($model->left));
        $I->assertTrue(isset($model->right));
        $I->assertTrue(isset($model->level));
    }

    public function testHazeltreeCallGetter(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Test');
        $model->save();

        $I->assertEquals('1', $model->getPath());
        $I->assertEquals(1, $model->getLevel());
        $I->assertNotNull($model->getLeft());
        $I->assertNotNull($model->getRight());
    }

    public function testHazeltreeIsRoot(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Root');
        $model->save();

        $I->assertTrue($model->isRoot());

        $child = new CombinedTraitsModel();
        $child->setName('Child');
        $child->saveInto($model);

        $I->assertFalse($child->isRoot());
    }


    public function testMockElasticSetAndGet(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();

        $model->title = 'Test Title';
        $model->description = 'Test Description';
        $model->price = 99.99;
        $model->active = true;

        $I->assertEquals('Test Title', $model->title);
        $I->assertEquals('Test Description', $model->description);
        $I->assertEquals(99.99, $model->price);
        $I->assertTrue($model->active);
    }

    public function testMockElasticIsset(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();

        $I->assertFalse(isset($model->title));

        $model->title = 'Hello';

        $I->assertTrue(isset($model->title));
    }

    public function testMockElasticCallGetter(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->title = 'Getter Test';

        $I->assertEquals('Getter Test', $model->getTitle());
    }

    public function testMockElasticCallSetter(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();

        $model->setTitle('Setter Test');

        $I->assertEquals('Setter Test', $model->title);
    }


    public function testBothTraitsWorkTogether(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Combined');

        $model->title = 'Combined Test';
        $model->price = 100.50;

        $model->save();

        $I->assertEquals('1', $model->path);
        $I->assertEquals(1, $model->level);

        $I->assertEquals('Combined Test', $model->title);
        $I->assertEquals(100.50, $model->price);
    }

    public function testBothTraitsIssetWorkTogether(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Test');
        $model->save();

        $I->assertTrue(isset($model->path));
        $I->assertTrue(isset($model->level));

        $I->assertFalse(isset($model->title));

        $model->title = 'Test';

        $I->assertTrue(isset($model->title));
    }

    public function testBothTraitsCallWorkTogether(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Call Test');
        $model->save();

        $model->setTitle('Title via call');
        $model->setPrice(50.00);

        $I->assertEquals('1', $model->getPath());
        $I->assertEquals(1, $model->getLevel());
        $I->assertEquals('Title via call', $model->getTitle());
        $I->assertEquals(50.00, $model->getPrice());
    }

    public function testPriorityHazeltreeOverElastic(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();
        $model->setName('Priority Test');
        $model->save();

        $I->assertEquals('1', $model->path);

        $model->title = 'Priority Test';
        $I->assertEquals('Priority Test', $model->title);
    }

    public function testNoConflictBetweenTraits(HazeltreeCombinedTraitsTester $I): void
    {
        $model = new CombinedTraitsModel();

        $I->assertInstanceOf(CombinedTraitsModel::class, $model);

        $model->setName('No Conflict');
        $model->save();
        $I->assertEquals('1', $model->path);

        $model->title = 'No Conflict Title';
        $I->assertEquals('No Conflict Title', $model->title);
    }

    public function testTreeOperationsWithElasticProperties(HazeltreeCombinedTraitsTester $I): void
    {
        $root = new CombinedTraitsModel();
        $root->setName('Root');
        $root->title = 'Root Category';
        $root->active = true;
        $root->save();

        $child = new CombinedTraitsModel();
        $child->setName('Child');
        $child->title = 'Child Category';
        $child->price = 25.00;
        $child->saveInto($root);

        $I->assertEquals('1', $root->path);
        $I->assertEquals('1.1', $child->path);
        $I->assertEquals(1, $root->level);
        $I->assertEquals(2, $child->level);

        $I->assertEquals('Root Category', $root->title);
        $I->assertEquals('Child Category', $child->title);
        $I->assertTrue($root->active);
        $I->assertEquals(25.00, $child->price);
    }

    public function testQueryWithTreeMethods(HazeltreeCombinedTraitsTester $I): void
    {
        $root = new CombinedTraitsModel();
        $root->setName('Root');
        $root->title = 'Root';
        $root->save();

        $child1 = new CombinedTraitsModel();
        $child1->setName('Child 1');
        $child1->title = 'First Child';
        $child1->saveInto($root);

        $child2 = new CombinedTraitsModel();
        $child2->setName('Child 2');
        $child2->title = 'Second Child';
        $child2->saveInto($root);

        $children = $root->relativeQuery()->children()->all();
        $I->assertCount(2, $children);

        $I->assertEquals('1.1', $children[0]->path);
        $I->assertEquals('1.2', $children[1]->path);
        $I->assertEquals('Child 1', $children[0]->getName());
        $I->assertEquals('Child 2', $children[1]->getName());
    }

    public function testMoveNodeWithElasticProperties(HazeltreeCombinedTraitsTester $I): void
    {
        $root1 = new CombinedTraitsModel();
        $root1->setName('Root 1');
        $root1->save();

        $root2 = new CombinedTraitsModel();
        $root2->setName('Root 2');
        $root2->save();

        $child = new CombinedTraitsModel();
        $child->setName('Child');
        $child->title = 'Movable Child';
        $child->price = 42.00;
        $child->saveInto($root1);

        $I->assertEquals('1.1', $child->path);
        $I->assertEquals('Movable Child', $child->title);

        $child->saveInto($root2);

        $I->assertEquals('2.1', $child->path);

        $I->assertEquals('Movable Child', $child->title);
        $I->assertEquals(42.00, $child->price);
    }

    public function testDeleteNodeWithElasticProperties(HazeltreeCombinedTraitsTester $I): void
    {
        $root = new CombinedTraitsModel();
        $root->setName('Root');
        $root->save();

        $child1 = new CombinedTraitsModel();
        $child1->setName('Child 1');
        $child1->title = 'To Delete';
        $child1->saveInto($root);

        $child2 = new CombinedTraitsModel();
        $child2->setName('Child 2');
        $child2->title = 'To Keep';
        $child2->saveInto($root);

        $child1->delete();

        $child2->refresh();
        $I->assertEquals('1.1', $child2->path);
        $I->assertEquals('To Keep', $child2->title);

        $count = CombinedTraitsModel::query()->count();
        $I->assertEquals(2, $count);
    }
}
