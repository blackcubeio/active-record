<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\HazeltreeTrait;

use Blackcube\ActiveRecord\Hazeltree\Exceptions\InvalidItemConfigurationException;
use Blackcube\ActiveRecord\Hazeltree\Helpers\TreeHelper;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\HazeltreeTraitTester;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\NodeWithParentMagic;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\SimpleNode;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\TreeNode;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\TreeNodeFactory;

/**
 * Integration tests for HazeltreeTrait with MySQL database.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class HazeltreeTraitCest
{
    use DatabaseCestTrait;


    public function testMagicGetReturnsTreeProperties(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test', '1.2.3');
        $node = $this->factory->findById($id);

        $I->assertEquals('1.2.3', $node->path);
        $I->assertEquals(3, $node->level);
        $I->assertIsFloat($node->left);
        $I->assertIsFloat($node->right);
        $I->assertLessThan($node->right, $node->left);
    }

    public function testMagicSetThrowsForReadonlyProperties(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test', '1');
        $node = $this->factory->findById($id);

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->path = '1.2.3';
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->left = 1.5;
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->right = 2.5;
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->level = 3;
        });
    }

    public function testMagicIsset(HazeltreeTraitTester $I): void
    {
        $node = new TreeNode();

        $I->assertFalse(isset($node->path));
        $I->assertFalse(isset($node->left));

        $id = $this->factory->insert('Test', '1');
        $loadedNode = $this->factory->findById($id);

        $I->assertTrue(isset($loadedNode->path));
        $I->assertTrue(isset($loadedNode->left));
    }

    public function testMagicCallGetterWorks(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test', '1.2');
        $node = $this->factory->findById($id);

        $I->assertEquals('1.2', $node->getPath());
        $I->assertEquals(2, $node->getLevel());
        $I->assertIsFloat($node->getLeft());
        $I->assertIsFloat($node->getRight());
    }

    public function testMagicCallSetterThrowsForReadonlyProperties(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test', '1');
        $node = $this->factory->findById($id);

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setPath('1.2');
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setLeft(1.0);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setRight(2.0);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setLevel(2);
        });
    }


    public function testMagicSetDelegatesToParentForNonTreeProperties(HazeltreeTraitTester $I): void
    {
        $node = new NodeWithParentMagic();

        $node->dynamicAttr = 'Dynamic Value';

        $I->assertEquals('Dynamic Value', $node->dynamicAttr);
    }

    public function testMagicGetDelegatesToParentForNonTreeProperties(HazeltreeTraitTester $I): void
    {
        $node = new NodeWithParentMagic();

        $node->dynamicAttr = 'Test Value';
        $I->assertEquals('Test Value', $node->dynamicAttr);
    }

    public function testMagicIssetDelegatesToParentForNonTreeProperties(HazeltreeTraitTester $I): void
    {
        $node = new NodeWithParentMagic();

        $I->assertFalse(isset($node->dynamicAttr));

        $node->dynamicAttr = 'Value';
        $I->assertTrue(isset($node->dynamicAttr));
    }

    public function testMagicCallDelegatesToParentForNonTreeMethods(HazeltreeTraitTester $I): void
    {
        $node = new NodeWithParentMagic();

        $node->setDynamicProperty('myKey', 'Call Value');
        $I->assertEquals('Call Value', $node->getDynamicProperty('myKey'));
    }


    public function testTreeHelperCalculatesCorrectValues(HazeltreeTraitTester $I): void
    {
        $path = '1';
        $matrix = TreeHelper::convert($path);
        $left = TreeHelper::getLeft($matrix);
        $right = TreeHelper::getRight($matrix);
        $level = TreeHelper::getLevel($path);

        $I->assertEquals(1.0, $left);
        $I->assertEquals(2.0, $right);
        $I->assertEquals(1, $level);
    }

    public function testTreeHelperDeepPath(HazeltreeTraitTester $I): void
    {
        $path = '1.2.3';
        $matrix = TreeHelper::convert($path);
        $left = TreeHelper::getLeft($matrix);
        $right = TreeHelper::getRight($matrix);
        $level = TreeHelper::getLevel($path);

        $I->assertEquals(3, $level);
        $I->assertLessThan($right, $left);
    }


    public function testIsRootForLevel1(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Root', '1');
        $node = $this->factory->findById($id);

        $I->assertTrue($node->isRoot());
    }

    public function testIsRootForLevel2(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $id = $this->factory->insert('Child', '1.1');
        $node = $this->factory->findById($id);

        $I->assertFalse($node->isRoot());
    }


    public function testSaveAndLoadNode(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test Node', '1.2');
        $loaded = $this->factory->findById($id);

        $I->assertNotNull($loaded);
        $I->assertEquals('Test Node', $loaded->getName());
        $I->assertEquals('1.2', $loaded->path);
        $I->assertEquals(2, $loaded->level);
    }

    public function testSaveMultipleNodes(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');
        $this->factory->insert('Child 2', '1.2');

        $nodes = TreeNode::query()->orderBy(['left' => SORT_ASC])->all();

        $I->assertCount(3, $nodes);
        $I->assertEquals('Root', $nodes[0]->getName());
        $I->assertEquals('Child 1', $nodes[1]->getName());
        $I->assertEquals('Child 2', $nodes[2]->getName());
    }


    public function testCanMoveToUnrelatedBranch(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $id1 = $this->factory->insert('Branch 1', '1.1');
        $this->factory->insert('Branch 2', '1.2');

        $node = $this->factory->findById($id1);

        $I->assertTrue($node->canMove('1.2'));
        $I->assertTrue($node->canMove('1.2.1'));
    }

    public function testCannotMoveIntoOwnSubtree(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $id = $this->factory->insert('Parent', '1.1');
        $this->factory->insert('Child', '1.1.1');

        $node = $this->factory->findById($id);

        $I->assertFalse($node->canMove('1.1'));
        $I->assertFalse($node->canMove('1.1.1'));
        $I->assertFalse($node->canMove('1.1.2'));
    }


    public function testSaveIntoNewRecord(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $root = $this->factory->findById($rootId);

        $child = new TreeNode();
        $child->setName('Child');
        $child->saveInto($root);

        $I->assertEquals('1.1', $child->path);
        $I->assertEquals(2, $child->level);
    }

    public function testSaveIntoWithExistingChildren(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');
        $root = $this->factory->findById($rootId);

        $child2 = new TreeNode();
        $child2->setName('Child 2');
        $child2->saveInto($root);

        $I->assertEquals('1.2', $child2->path);
    }


    public function testSaveIntoWithPathString(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');

        $child = new TreeNode();
        $child->setName('Child');
        $child->saveInto('1');

        $I->assertEquals('1.1', $child->path);
        $I->assertEquals(2, $child->level);
    }

    public function testSaveBeforeWithPathString(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('Child 2', '1.2');

        $child1 = new TreeNode();
        $child1->setName('Child 1');
        $child1->saveBefore('1.2');


        $I->assertEquals('1.2', $child1->path);

        $child2 = TreeNode::query()->where(['name' => 'Child 2'])->one();
        $I->assertEquals('1.3', $child2->path);
    }

    public function testSaveAfterWithPathString(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');

        $child2 = new TreeNode();
        $child2->setName('Child 2');
        $child2->saveAfter('1.1');

        $I->assertEquals('1.2', $child2->path);
    }

    public function testSaveIntoWithInvalidPathThrows(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');

        $child = new TreeNode();
        $child->setName('Child');

        $I->expectThrowable(InvalidItemConfigurationException::class, function () use ($child) {
            $child->saveInto('99.99.99');
        });
    }

    public function testSaveIntoExistingNodeWithPathString(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');
        $childId = $this->factory->insert('Child 2', '1.2');
        $this->factory->insert('Other Parent', '2');

        $child2 = $this->factory->findById($childId);
        $child2->saveInto('2');

        $child2->refresh();
        $I->assertEquals('2.1', $child2->path);
    }


    public function testSaveBeforeNewRecord(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $child1Id = $this->factory->insert('Child 1', '1.1');
        $child1 = $this->factory->findById($child1Id);

        $newChild = new TreeNode();
        $newChild->setName('New Child');
        $newChild->saveBefore($child1);

        $I->assertEquals('1.1', $newChild->path);

        $child1->refresh();
        $I->assertEquals('1.2', $child1->path);
    }


    public function testSaveAfterNewRecord(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $child1Id = $this->factory->insert('Child 1', '1.1');
        $child1 = $this->factory->findById($child1Id);

        $newChild = new TreeNode();
        $newChild->setName('New Child');
        $newChild->saveAfter($child1);

        $I->assertEquals('1.2', $newChild->path);
        $I->assertEquals('1.1', $child1->path);
    }

    public function testSaveAfterWithExistingNextSibling(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $child1Id = $this->factory->insert('Child 1', '1.1');
        $child2Id = $this->factory->insert('Child 2', '1.2');
        $child1 = $this->factory->findById($child1Id);
        $child2 = $this->factory->findById($child2Id);

        $newChild = new TreeNode();
        $newChild->setName('New Child');
        $newChild->saveAfter($child1);

        $I->assertEquals('1.2', $newChild->path);

        $child2->refresh();
        $I->assertEquals('1.3', $child2->path);
    }


    public function testSaveWithoutPathCreatesRootNode(HazeltreeTraitTester $I): void
    {
        $node = new TreeNode();
        $node->setName('Auto Root');
        $node->save();

        $I->assertEquals('1', $node->path);
        $I->assertEquals(1, $node->level);
        $I->assertTrue($node->isRoot());
    }

    public function testSaveWithoutPathCreatesNextRoot(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root 1', '1');

        $node = new TreeNode();
        $node->setName('Root 2');
        $node->save();

        $I->assertEquals('2', $node->path);
        $I->assertEquals(1, $node->level);
        $I->assertTrue($node->isRoot());
    }


    public function testSaveBeforeRootWorks(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $root = $this->factory->findById($rootId);

        $node = new TreeNode();
        $node->setName('BeforeRoot');
        $node->saveBefore($root);

        $I->assertEquals('1', $node->path);
        $I->assertEquals(1, $node->level);

        $root->refresh();
        $I->assertEquals('2', $root->path);
    }

    public function testSaveAfterRootWorks(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $root = $this->factory->findById($rootId);

        $node = new TreeNode();
        $node->setName('AfterRoot');
        $node->saveAfter($root);

        $I->assertEquals('1', $root->path);
        $I->assertEquals('2', $node->path);
        $I->assertEquals(1, $node->level);
    }


    public function testFullIntegrationScenario(HazeltreeTraitTester $I): void
    {
        $root = new TreeNode();
        $root->setName('Root');
        $root->save();

        $I->assertEquals('1', $root->path);
        $I->assertEquals(1, $root->level);

        $node2 = new TreeNode();
        $node2->setName('Node 2');
        $node2->saveInto($root);

        $I->assertEquals('1.1', $node2->path);
        $I->assertEquals(2, $node2->level);

        $node3 = new TreeNode();
        $node3->setName('Node 3');
        $node3->saveInto($node2);

        $I->assertEquals('1.1.1', $node3->path);
        $I->assertEquals(3, $node3->level);

        $node4 = new TreeNode();
        $node4->setName('Node 4');
        $node4->saveAfter($node2);

        $I->assertEquals('1.2', $node4->path);

        $node4->saveInto($root);

        $node2->refresh();
        $node3->refresh();
        $node4->refresh();

        $I->assertEquals('1.1', $node2->path);
        $I->assertEquals('1.1.1', $node3->path);
        $I->assertEquals('1.2', $node4->path);

        $node5 = new TreeNode();
        $node5->setName('Node 5');
        $node5->saveBefore($node2);

        $I->assertEquals('1.1', $node5->path);

        $node2->refresh();
        $node3->refresh();
        $node4->refresh();
        $I->assertEquals('1.2', $node2->path);
        $I->assertEquals('1.2.1', $node3->path);
        $I->assertEquals('1.3', $node4->path);
    }


    public function testDeleteNodeClosesGap(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');
        $child2Id = $this->factory->insert('Child 2', '1.2');
        $child3Id = $this->factory->insert('Child 3', '1.3');

        $child2 = $this->factory->findById($child2Id);
        $child2->delete();

        $child1 = TreeNode::query()->where(['name' => 'Child 1'])->one();
        $child3 = $this->factory->findById($child3Id);
        $child3->refresh();

        $I->assertEquals('1.1', $child1->path);
        $I->assertEquals('1.2', $child3->path);

        $allNodes = TreeNode::query()->orderBy(['left' => SORT_ASC])->all();
        $I->assertCount(3, $allNodes);
    }

    public function testDeleteNodeWithChildrenClosesGap(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $child1Id = $this->factory->insert('Child 1', '1.1');
        $this->factory->insert('Grandchild', '1.1.1');
        $child2Id = $this->factory->insert('Child 2', '1.2');

        $child1 = $this->factory->findById($child1Id);
        $child1->delete();

        $child2 = $this->factory->findById($child2Id);
        $child2->refresh();

        $I->assertEquals('1.1', $child2->path);

        $allNodes = TreeNode::query()->orderBy(['left' => SORT_ASC])->all();
        $I->assertCount(2, $allNodes);
        $I->assertEquals('Root', $allNodes[0]->getName());
        $I->assertEquals('Child 2', $allNodes[1]->getName());
    }

    public function testDeleteLastSiblingDoesNotAffectOthers(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $child1Id = $this->factory->insert('Child 1', '1.1');
        $child2Id = $this->factory->insert('Child 2', '1.2');
        $child3Id = $this->factory->insert('Child 3', '1.3');

        $child3 = $this->factory->findById($child3Id);
        $child3->delete();

        $child1 = $this->factory->findById($child1Id);
        $child2 = $this->factory->findById($child2Id);

        $I->assertEquals('1.1', $child1->path);
        $I->assertEquals('1.2', $child2->path);

        $allNodes = TreeNode::query()->orderBy(['left' => SORT_ASC])->all();
        $I->assertCount(3, $allNodes);
    }

    public function testDeleteRootWorks(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $this->factory->insert('Child', '1.1');
        $root = $this->factory->findById($rootId);

        $deleted = $root->delete();

        $I->assertEquals(2, $deleted, 'Root and child should be deleted');
        $I->assertNull(TreeNode::query()->where(['id' => $rootId])->one());
    }

    public function testDeleteFirstSiblingShiftsAll(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $child1Id = $this->factory->insert('Child 1', '1.1');
        $child2Id = $this->factory->insert('Child 2', '1.2');
        $child3Id = $this->factory->insert('Child 3', '1.3');

        $child1 = $this->factory->findById($child1Id);
        $child1->delete();

        $child2 = $this->factory->findById($child2Id);
        $child3 = $this->factory->findById($child3Id);
        $child2->refresh();
        $child3->refresh();

        $I->assertEquals('1.1', $child2->path);
        $I->assertEquals('1.2', $child3->path);
    }


    public function testCreateMultipleRoots(HazeltreeTraitTester $I): void
    {
        $root1 = new TreeNode();
        $root1->setName('Root 1');
        $root1->save();

        $root2 = new TreeNode();
        $root2->setName('Root 2');
        $root2->save();

        $root3 = new TreeNode();
        $root3->setName('Root 3');
        $root3->save();

        $I->assertEquals('1', $root1->path);
        $I->assertEquals('2', $root2->path);
        $I->assertEquals('3', $root3->path);

        $I->assertTrue($root1->isRoot());
        $I->assertTrue($root2->isRoot());
        $I->assertTrue($root3->isRoot());
    }

    public function testMoveNodeBetweenRoots(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root 1', '1');
        $childId = $this->factory->insert('Child 1', '1.1');
        $this->factory->insert('Grandchild', '1.1.1');
        $this->factory->insert('Root 2', '2');

        $child = $this->factory->findById($childId);
        $child->saveInto('2');

        $child->refresh();
        $I->assertEquals('2.1', $child->path);
        $I->assertEquals(2, $child->level);

        $grandchild = TreeNode::query()->where(['name' => 'Grandchild'])->one();
        $I->assertEquals('2.1.1', $grandchild->path);
    }

    public function testMoveNodeFromOneRootToAnother(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root 1', '1');
        $this->factory->insert('Child A', '1.1');
        $this->factory->insert('Root 2', '2');
        $childBId = $this->factory->insert('Child B', '2.1');

        $childB = $this->factory->findById($childBId);
        $childB->saveInto('1');

        $childB->refresh();
        $I->assertEquals('1.2', $childB->path);

        $childA = TreeNode::query()->where(['name' => 'Child A'])->one();
        $I->assertEquals('1.1', $childA->path);
    }

    public function testSaveNewNodeIntoSecondRoot(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root 1', '1');
        $this->factory->insert('Root 2', '2');

        $newChild = new TreeNode();
        $newChild->setName('New Child');
        $newChild->saveInto('2');

        $I->assertEquals('2.1', $newChild->path);
        $I->assertEquals(2, $newChild->level);
    }

    public function testCanMoveAcrossRoots(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root 1', '1');
        $childId = $this->factory->insert('Child', '1.1');
        $this->factory->insert('Root 2', '2');

        $child = $this->factory->findById($childId);

        $I->assertTrue($child->canMove('2'));
        $I->assertTrue($child->canMove('2.1'));

        $I->assertFalse($child->canMove('1.1'));
        $I->assertFalse($child->canMove('1.1.1'));
    }


    public function testFindMethodReturnsQueryWithNodeContext(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');
        $this->factory->insert('Child 2', '1.2');

        $root = TreeNode::query()->where(['path' => '1'])->one();
        $query = $root->relativeQuery();

        $I->assertInstanceOf(\Yiisoft\ActiveRecord\ActiveQueryInterface::class, $query);
    }


    public function testTreeConsistencyAfterMultipleOperations(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $this->factory->insert('C', '1.3');

        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $a = TreeNode::query()->where(['name' => 'A'])->one();
        $b->saveBefore($a);

        $allNodes = TreeNode::query()->all();
        foreach ($allNodes as $node) {
            $I->assertLessThan($node->right, $node->left, 'Node ' . $node->path . ': left should be less than right');
        }

        $root = TreeNode::query()->where(['path' => '1'])->one();
        $children = $root->relativeQuery()->children()->all();

        for ($i = 0; $i < count($children) - 1; $i++) {
            $current = $children[$i];
            $next = $children[$i + 1];
            $I->assertLessThanOrEqual(
                $next->left,
                $current->right,
                'Sibling ' . $current->path . ' should end before ' . $next->path . ' starts'
            );
        }
    }

    public function testTreeConsistencyAfterDeleteAndInsert(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $this->factory->insert('C', '1.3');

        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $b->delete();

        $root = TreeNode::query()->where(['path' => '1'])->one();
        $newNode = new TreeNode();
        $newNode->setName('D');
        $newNode->saveInto($root);

        $allNodes = TreeNode::query()->orderBy(['left' => SORT_ASC])->all();

        $I->assertCount(4, $allNodes);

        $paths = array_map(fn($n) => $n->path, $allNodes);
        $I->assertEquals([
            '1',
            '1.1',
            '1.2',
            '1.3',
        ], $paths);
    }


    public function testSaveAfterExistingNodeMovesIt(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $cId = $this->factory->insert('C', '1.3');

        $c = $this->factory->findById($cId);
        $a = TreeNode::query()->where(['path' => '1.1'])->one();
        $c->saveAfter($a);

        $c->refresh();
        $I->assertEquals('1.2', $c->path);

        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $I->assertEquals('1.3', $b->path);
    }

    public function testSaveAfterExistingNodeWhenTargetIsLastSibling(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $aId = $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $this->factory->insert('C', '1.3');

        $a = $this->factory->findById($aId);
        $c = TreeNode::query()->where(['path' => '1.3'])->one();
        $a->saveAfter($c);

        $a->refresh();
        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $c->refresh();

        $I->assertEquals('1.1', $b->path);
        $I->assertEquals('1.2', $c->path);
        $I->assertEquals('1.3', $a->path);
    }

    public function testSaveAfterExistingNodeWithChildren(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $bId = $this->factory->insert('B', '1.2');
        $this->factory->insert('B1', '1.2.1');
        $this->factory->insert('C', '1.3');

        $b = $this->factory->findById($bId);
        $c = TreeNode::query()->where(['path' => '1.3'])->one();
        $b->saveAfter($c);

        $b->refresh();
        $I->assertEquals('1.3', $b->path);

        $b1 = TreeNode::query()->where(['name' => 'B1'])->one();
        $I->assertEquals('1.3.1', $b1->path);

        $c->refresh();
        $I->assertEquals('1.2', $c->path);
    }


    public function testMoveIntoWithExistingSiblingsAfterTarget(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $aId = $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $this->factory->insert('C', '1.3');

        $a = $this->factory->findById($aId);

        $this->factory->insert('Root2', '2');
        $a->saveInto('2');

        $a->refresh();
        $I->assertEquals('2.1', $a->path);

        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $c = TreeNode::query()->where(['name' => 'C'])->one();
        $I->assertEquals('1.1', $b->path);
        $I->assertEquals('1.2', $c->path);
    }

    public function testMoveIntoTargetWithExistingChildren(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root1', '1');
        $aId = $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $this->factory->insert('Root2', '2');
        $this->factory->insert('X', '2.1');

        $a = $this->factory->findById($aId);
        $a->saveInto('2');

        $a->refresh();
        $I->assertEquals('2.2', $a->path);

        $x = TreeNode::query()->where(['name' => 'X'])->one();
        $I->assertEquals('2.1', $x->path);

        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $I->assertEquals('1.1', $b->path);
    }

    public function testMoveIntoBumpsExistingChildrenWhenInsertingInMiddle(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $this->factory->insert('A1', '1.1.1');
        $this->factory->insert('A2', '1.1.2');
        $bId = $this->factory->insert('B', '1.2');

        $b = $this->factory->findById($bId);
        $b->saveInto('1.1');

        $b->refresh();
        $I->assertEquals('1.1.3', $b->path);

        $a1 = TreeNode::query()->where(['name' => 'A1'])->one();
        $a2 = TreeNode::query()->where(['name' => 'A2'])->one();
        $I->assertEquals('1.1.1', $a1->path);
        $I->assertEquals('1.1.2', $a2->path);
    }


    public function testMoveIntoWithDeepTreeBumpsBackCorrectly(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $aId = $this->factory->insert('A', '1.1');
        $this->factory->insert('A1', '1.1.1');
        $this->factory->insert('A2', '1.1.2');
        $this->factory->insert('B', '1.2');
        $this->factory->insert('B1', '1.2.1');
        $this->factory->insert('Root2', '2');

        $a = $this->factory->findById($aId);
        $a->saveInto('2');

        $a->refresh();
        $I->assertEquals('2.1', $a->path);

        $a1 = TreeNode::query()->where(['name' => 'A1'])->one();
        $a2 = TreeNode::query()->where(['name' => 'A2'])->one();
        $I->assertEquals('2.1.1', $a1->path);
        $I->assertEquals('2.1.2', $a2->path);

        $b = TreeNode::query()->where(['name' => 'B'])->one();
        $b1 = TreeNode::query()->where(['name' => 'B1'])->one();
        $I->assertEquals('1.1', $b->path);
        $I->assertEquals('1.1.1', $b1->path);
    }

    public function testMoveIntoDoesNotBumpSiblingsBeforeMovedNode(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root1', '1');
        $this->factory->insert('A', '1.1');
        $bId = $this->factory->insert('B', '1.2');
        $this->factory->insert('C', '1.3');
        $this->factory->insert('Root2', '2');

        $b = $this->factory->findById($bId);
        $b->saveInto('2');

        $b->refresh();
        $I->assertEquals('2.1', $b->path);

        $a = TreeNode::query()->where(['name' => 'A'])->one();
        $I->assertEquals('1.1', $a->path);

        $c = TreeNode::query()->where(['name' => 'C'])->one();
        $I->assertEquals('1.2', $c->path);

        $root1 = TreeNode::query()->where(['name' => 'Root1'])->one();
        $root2 = TreeNode::query()->where(['name' => 'Root2'])->one();
        $I->assertEquals('1', $root1->path);
        $I->assertEquals('2', $root2->path);
    }


    public function testGetNextSiblingsTreesForRoot(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root1', '1');
        $this->factory->insert('Child1', '1.1');
        $this->factory->insert('Root2', '2');
        $this->factory->insert('Child2', '2.1');
        $this->factory->insert('Root3', '3');

        $root1 = TreeNode::query()->where(['path' => '1'])->one();
        $nextRoots = $root1->relativeQuery()->siblings()->next()->includeDescendants()->all();

        $I->assertCount(3, $nextRoots);
        $paths = array_map(fn($n) => $n->path, $nextRoots);
        $I->assertContains('2', $paths);
        $I->assertContains('2.1', $paths);
        $I->assertContains('3', $paths);
    }


    public function testSaveBeforeExistingNodeMovesIt(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $this->factory->insert('B', '1.2');
        $cId = $this->factory->insert('C', '1.3');

        $c = $this->factory->findById($cId);
        $b = TreeNode::query()->where(['path' => '1.2'])->one();
        $c->saveBefore($b);

        $c->refresh();
        $I->assertEquals('1.2', $c->path);

        $b->refresh();
        $I->assertEquals('1.3', $b->path);
    }

    public function testSaveBeforeExistingNodeWithChildrenMovesEntireSubtree(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $this->factory->insert('A', '1.1');
        $bId = $this->factory->insert('B', '1.2');
        $this->factory->insert('B1', '1.2.1');
        $this->factory->insert('B2', '1.2.2');
        $this->factory->insert('C', '1.3');

        $b = $this->factory->findById($bId);
        $a = TreeNode::query()->where(['path' => '1.1'])->one();
        $b->saveBefore($a);

        $b->refresh();
        $I->assertEquals('1.1', $b->path);

        $b1 = TreeNode::query()->where(['name' => 'B1'])->one();
        $b2 = TreeNode::query()->where(['name' => 'B2'])->one();
        $I->assertEquals('1.1.1', $b1->path);
        $I->assertEquals('1.1.2', $b2->path);

        $a->refresh();
        $I->assertEquals('1.2', $a->path);

        $c = TreeNode::query()->where(['name' => 'C'])->one();
        $I->assertEquals('1.3', $c->path);
    }


    public function testStandaloneMagicGetReturnsTreeProperties(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();
        $reflection = new \ReflectionClass($node);

        $pathProp = $reflection->getProperty('path');
        $pathProp->setAccessible(true);
        $pathProp->setValue($node, '1.2.3');

        $levelProp = $reflection->getProperty('level');
        $levelProp->setAccessible(true);
        $levelProp->setValue($node, 3);

        $leftProp = $reflection->getProperty('left');
        $leftProp->setAccessible(true);
        $leftProp->setValue($node, 1.5);

        $rightProp = $reflection->getProperty('right');
        $rightProp->setAccessible(true);
        $rightProp->setValue($node, 1.6);

        $I->assertEquals('1.2.3', $node->path);
        $I->assertEquals(3, $node->level);
        $I->assertEquals(1.5, $node->left);
        $I->assertEquals(1.6, $node->right);
    }

    public function testStandaloneMagicGetReturnsNullForUnknownProperty(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $value = @$node->unknownProperty;
        $I->assertNull($value);
    }

    public function testStandaloneMagicSetThrowsForReadonlyTreeProperties(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->path = '1.2.3';
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->left = 1.5;
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->right = 1.6;
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->level = 3;
        });
    }

    public function testStandaloneMagicSetTriggersWarningForUnknownProperty(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        @($node->unknownProperty = 'value');
        $I->assertTrue(true);
    }

    public function testStandaloneMagicIssetReturnsTrueForSetProperties(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $I->assertFalse(isset($node->path));
        $I->assertFalse(isset($node->level));

        $reflection = new \ReflectionClass($node);
        $pathProp = $reflection->getProperty('path');
        $pathProp->setAccessible(true);
        $pathProp->setValue($node, '1.2');

        $levelProp = $reflection->getProperty('level');
        $levelProp->setAccessible(true);
        $levelProp->setValue($node, 2);

        $I->assertTrue(isset($node->path));
        $I->assertTrue(isset($node->level));
    }

    public function testStandaloneMagicIssetReturnsFalseForUnknownProperty(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $I->assertFalse(@isset($node->unknownProperty));
    }

    public function testStandaloneMagicCallGetterWorks(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $reflection = new \ReflectionClass($node);
        $pathProp = $reflection->getProperty('path');
        $pathProp->setAccessible(true);
        $pathProp->setValue($node, '1.2');

        $levelProp = $reflection->getProperty('level');
        $levelProp->setAccessible(true);
        $levelProp->setValue($node, 2);

        $I->assertEquals('1.2', $node->getPath());
        $I->assertEquals(2, $node->getLevel());
    }

    public function testStandaloneMagicCallSetterThrowsForReadonlyProperties(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setPath('1.2');
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setLeft(1.0);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setRight(2.0);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->setLevel(2);
        });
    }

    public function testStandaloneMagicCallThrowsForUnknownMethod(HazeltreeTraitTester $I): void
    {
        $node = new SimpleNode();

        $I->expectThrowable(\Throwable::class, function () use ($node) {
            $node->unknownMethod();
        });
    }


    public function testSetMethodThrowsForTreeProperties(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test', '1');
        $node = $this->factory->findById($id);

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->set('left', 1.5);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->set('right', 2.5);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->set('level', 5);
        });

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->set('path', '1.2.3');
        });
    }

    public function testProtectHazeltreeAllowsWriteWhenDisabled(HazeltreeTraitTester $I): void
    {
        $id = $this->factory->insert('Test', '1');
        $node = $this->factory->findById($id);

        $node->protectHazeltree(false);

        $node->left = 99.0;
        $node->right = 100.0;
        $node->level = 42;
        $node->path = '9.9.9';

        $I->assertEquals(99.0, $node->left);
        $I->assertEquals(100.0, $node->right);
        $I->assertEquals(42, $node->level);
        $I->assertEquals('9.9.9', $node->path);

        $node->protectHazeltree(true);

        $I->expectThrowable(\Error::class, function () use ($node) {
            $node->left = 1.0;
        });
    }


    public function testGetSubtreeDepthNoChildren(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $root = $this->factory->findById($rootId);

        $I->assertEquals(0, $root->getSubtreeDepth());
    }

    public function testGetSubtreeDepthOneLevel(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $this->factory->insert('Child 1', '1.1');
        $this->factory->insert('Child 2', '1.2');
        $root = $this->factory->findById($rootId);

        $I->assertEquals(1, $root->getSubtreeDepth());
    }

    public function testGetSubtreeDepthTwoLevels(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $this->factory->insert('Child', '1.1');
        $this->factory->insert('Grandchild', '1.1.1');
        $root = $this->factory->findById($rootId);

        $I->assertEquals(2, $root->getSubtreeDepth());
    }

    public function testGetSubtreeDepthFromMiddleNode(HazeltreeTraitTester $I): void
    {
        $this->factory->insert('Root', '1');
        $childId = $this->factory->insert('Child', '1.1');
        $this->factory->insert('Grandchild 1', '1.1.1');
        $this->factory->insert('Grandchild 2', '1.1.2');
        $this->factory->insert('Great-grandchild', '1.1.1.1');
        $child = $this->factory->findById($childId);

        $I->assertEquals(2, $child->getSubtreeDepth());
    }

    public function testGetMaxLevelIfMoveBefore(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $this->factory->insert('Source Child', '2.1');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->assertEquals(2, $source->getMaxLevelIfMoveBefore($root));
    }

    public function testGetMaxLevelIfMoveAfter(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $this->factory->insert('Source Child', '2.1');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->assertEquals(2, $source->getMaxLevelIfMoveAfter($root));
    }

    public function testGetMaxLevelIfMoveInto(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $this->factory->insert('Source Child', '2.1');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->assertEquals(3, $source->getMaxLevelIfMoveInto($root));
    }

    public function testWouldExceedMaxLevelReturnsFalse(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $this->factory->insert('Source Child', '2.1');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->assertFalse($source->wouldExceedMaxLevel($root, 'into', 5));
        $I->assertFalse($source->wouldExceedMaxLevel($root, 'before', 5));
        $I->assertFalse($source->wouldExceedMaxLevel($root, 'after', 5));
    }

    public function testWouldExceedMaxLevelReturnsTrue(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $this->factory->insert('Source Child', '2.1');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->assertTrue($source->wouldExceedMaxLevel($root, 'into', 2));
    }

    public function testWouldExceedMaxLevelExactBoundary(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $this->factory->insert('Source Child', '2.1');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->assertFalse($source->wouldExceedMaxLevel($root, 'into', 3));
    }

    public function testWouldExceedMaxLevelInvalidModeThrows(HazeltreeTraitTester $I): void
    {
        $rootId = $this->factory->insert('Root', '1');
        $sourceId = $this->factory->insert('Source', '2');
        $root = $this->factory->findById($rootId);
        $source = $this->factory->findById($sourceId);

        $I->expectThrowable(\InvalidArgumentException::class, function () use ($source, $root) {
            $source->wouldExceedMaxLevel($root, 'invalid', 5);
        });
    }
}
