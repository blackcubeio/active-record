<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\HazeltreeTrait;

use Blackcube\ActiveRecord\Tests\Support\Hazeltree\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\HazeltreeTraitTester;
use Blackcube\ActiveRecord\Tests\Support\Hazeltree\TreeNode;

/**
 * Full integration test simulating a contributor's workflow.
 *
 * This test simulates building a complete website structure:
 * - Homepage with sections (About, Services, Contact)
 * - Blog with categories and articles
 * - Products catalog with categories
 *
 * Then performs various operations: reorganizing, moving content, deleting sections.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class ContributorIntegrationCest
{
    use DatabaseCestTrait;

    /**
     * Complete contributor workflow test.
     *
     * Scenario:
     * 1. Create initial website structure
     * 2. Add content to various sections
     * 3. Reorganize content (move pages between sections)
     * 4. Delete obsolete sections
     * 5. Verify tree integrity throughout
     */
    public function testCompleteContributorWorkflow(HazeltreeTraitTester $I): void
    {
        $I->wantTo('build a complete website structure as a contributor');

        $homepage = $this->createNode('Homepage');
        $homepage->save();
        $I->assertEquals('1', $homepage->path);
        $I->assertEquals(1, $homepage->level);

        $blog = $this->createNode('Blog');
        $blog->save();
        $I->assertEquals('2', $blog->path);

        $products = $this->createNode('Products');
        $products->save();
        $I->assertEquals('3', $products->path);

        $roots = TreeNode::query()->roots()->all();
        $I->assertCount(3, $roots);

        $I->wantTo('add pages under Homepage');

        $about = $this->createNode('About Us');
        $I->assertTrue($about->saveInto($homepage));
        $I->assertEquals('1.1', $about->path);
        $I->assertEquals(2, $about->level);

        $services = $this->createNode('Services');
        $I->assertTrue($services->saveInto($homepage));
        $I->assertEquals('1.2', $services->path);

        $contact = $this->createNode('Contact');
        $I->assertTrue($contact->saveInto($homepage));
        $I->assertEquals('1.3', $contact->path);

        $webDev = $this->createNode('Web Development');
        $I->assertTrue($webDev->saveInto($services));
        $I->assertEquals('1.2.1', $webDev->path);

        $mobileDev = $this->createNode('Mobile Development');
        $I->assertTrue($mobileDev->saveInto($services));
        $I->assertEquals('1.2.2', $mobileDev->path);

        $consulting = $this->createNode('Consulting');
        $I->assertTrue($consulting->saveInto($services));
        $I->assertEquals('1.2.3', $consulting->path);

        $homepageChildren = $homepage->relativeQuery()->children()->all();
        $I->assertCount(3, $homepageChildren);
        $I->assertEquals([
            'About Us',
            'Services',
            'Contact',
        ], array_map(fn($n) => $n->getName(), $homepageChildren));

        $servicesChildren = $services->relativeQuery()->children()->all();
        $I->assertCount(3, $servicesChildren);

        $I->wantTo('create blog categories and articles');

        $techCategory = $this->createNode('Technology');
        $I->assertTrue($techCategory->saveInto($blog));

        $lifestyleCategory = $this->createNode('Lifestyle');
        $I->assertTrue($lifestyleCategory->saveInto($blog));

        $newsCategory = $this->createNode('News');
        $I->assertTrue($newsCategory->saveInto($blog));

        $article1 = $this->createNode('Introduction to PHP 8.3');
        $I->assertTrue($article1->saveInto($techCategory));

        $article2 = $this->createNode('Understanding Nested Sets');
        $I->assertTrue($article2->saveInto($techCategory));

        $article3 = $this->createNode('Yii3 Framework Guide');
        $I->assertTrue($article3->saveInto($techCategory));

        $article4 = $this->createNode('Work-Life Balance Tips');
        $I->assertTrue($article4->saveInto($lifestyleCategory));

        $article5 = $this->createNode('Remote Work Best Practices');
        $I->assertTrue($article5->saveInto($lifestyleCategory));

        $blogChildren = $blog->relativeQuery()->children()->all();
        $I->assertCount(3, $blogChildren);

        $allBlogContent = $blog->relativeQuery()->children()->includeDescendants()->all();
        $I->assertCount(8, $allBlogContent);

        $I->wantTo('create product catalog');

        $electronics = $this->createNode('Electronics');
        $I->assertTrue($electronics->saveInto($products));

        $clothing = $this->createNode('Clothing');
        $I->assertTrue($clothing->saveInto($products));

        $phones = $this->createNode('Phones');
        $I->assertTrue($phones->saveInto($electronics));

        $laptops = $this->createNode('Laptops');
        $I->assertTrue($laptops->saveInto($electronics));

        $iphone = $this->createNode('iPhone 15');
        $I->assertTrue($iphone->saveInto($phones));

        $samsung = $this->createNode('Samsung Galaxy');
        $I->assertTrue($samsung->saveInto($phones));

        $macbook = $this->createNode('MacBook Pro');
        $I->assertTrue($macbook->saveInto($laptops));

        $this->assertTreeIntegrity($I);

        $I->wantTo('verify navigation queries work correctly');

        $iphoneAncestors = $iphone->relativeQuery()->parent()->includeAncestors()->all();
        $I->assertCount(3, $iphoneAncestors);
        $ancestorNames = array_map(fn($n) => $n->getName(), $iphoneAncestors);
        $I->assertContains('Products', $ancestorNames);
        $I->assertContains('Electronics', $ancestorNames);
        $I->assertContains('Phones', $ancestorNames);

        $techSiblings = $techCategory->relativeQuery()->siblings()->all();
        $I->assertCount(2, $techSiblings);
        $siblingNames = array_map(fn($n) => $n->getName(), $techSiblings);
        $I->assertContains('Lifestyle', $siblingNames);
        $I->assertContains('News', $siblingNames);

        $nextSibling = $techCategory->relativeQuery()->siblings()->next()->one();
        $I->assertNotNull($nextSibling);
        $I->assertEquals('Lifestyle', $nextSibling->getName());

        $prevSibling = $lifestyleCategory->relativeQuery()->siblings()->previous()->one();
        $I->assertNotNull($prevSibling);
        $I->assertEquals('Technology', $prevSibling->getName());

        $I->wantTo('move articles between blog categories');

        $article5 = TreeNode::query()->where(['name' => 'Remote Work Best Practices'])->one();
        $I->assertTrue($article5->saveInto($techCategory));

        $article5->refresh();
        $I->assertStringStartsWith('2.1.', $article5->path);

        $techArticles = $techCategory->relativeQuery()->children()->all();
        $I->assertCount(4, $techArticles);

        $lifestyleCategory->refresh();
        $lifestyleArticles = $lifestyleCategory->relativeQuery()->children()->all();
        $I->assertCount(1, $lifestyleArticles);

        $this->assertTreeIntegrity($I);

        $I->wantTo('reorder services by moving Consulting to first position');

        $consulting = TreeNode::query()->where(['name' => 'Consulting'])->one();
        $webDev = TreeNode::query()->where(['name' => 'Web Development'])->one();

        $I->assertTrue($consulting->saveBefore($webDev));

        $services->refresh();
        $servicesChildren = $services->relativeQuery()->children()->all();
        $childNames = array_map(fn($n) => $n->getName(), $servicesChildren);
        $I->assertEquals([
            'Consulting',
            'Web Development',
            'Mobile Development',
        ], $childNames);

        $this->assertTreeIntegrity($I);

        $I->wantTo('insert a new service after Web Development');

        $uiDesign = $this->createNode('UI/UX Design');
        $webDev = TreeNode::query()->where(['name' => 'Web Development'])->one();
        $I->assertTrue($uiDesign->saveAfter($webDev));

        $services->refresh();
        $servicesChildren = $services->relativeQuery()->children()->all();
        $childNames = array_map(fn($n) => $n->getName(), $servicesChildren);
        $I->assertEquals([
            'Consulting',
            'Web Development',
            'UI/UX Design',
            'Mobile Development',
        ], $childNames);

        $this->assertTreeIntegrity($I);

        $I->wantTo('move entire Electronics section under a new parent');

        $techProducts = $this->createNode('Tech Products');
        $techProducts->saveInto($products);

        $electronics = TreeNode::query()->where(['name' => 'Electronics'])->one();
        $I->assertTrue($electronics->saveInto($techProducts));

        $electronics->refresh();
        $phones = TreeNode::query()->where(['name' => 'Phones'])->one();
        $iphone = TreeNode::query()->where(['name' => 'iPhone 15'])->one();

        $I->assertEquals(3, $electronics->level);
        $I->assertEquals(4, $phones->level);
        $I->assertEquals(5, $iphone->level);

        $iphoneAncestors = $iphone->relativeQuery()->parent()->includeAncestors()->all();
        $ancestorNames = array_map(fn($n) => $n->getName(), $iphoneAncestors);
        $I->assertContains('Tech Products', $ancestorNames);
        $I->assertContains('Electronics', $ancestorNames);
        $I->assertContains('Phones', $ancestorNames);
        $I->assertContains('Products', $ancestorNames);

        $this->assertTreeIntegrity($I);

        $I->wantTo('delete the News category and all its content');

        $newsCategory = TreeNode::query()->where(['name' => 'News'])->one();
        $I->assertNotNull($newsCategory, 'News category should exist');
        $newsCategory->delete();

        $deletedCategory = TreeNode::query()->where(['name' => 'News'])->one();
        $I->assertNull($deletedCategory);

        $blog->refresh();
        $blogChildren = $blog->relativeQuery()->children()->all();
        $I->assertCount(2, $blogChildren);

        $this->assertTreeIntegrity($I);

        $I->wantTo('delete Phones category with all its products');

        $phones = TreeNode::query()->where(['name' => 'Phones'])->one();
        $phones->delete();

        $iphone = TreeNode::query()->where(['name' => 'iPhone 15'])->one();
        $samsung = TreeNode::query()->where(['name' => 'Samsung Galaxy'])->one();
        $I->assertNull($iphone);
        $I->assertNull($samsung);

        $electronics = TreeNode::query()->where(['name' => 'Electronics'])->one();
        $I->assertNotNull($electronics);
        $electronicsChildren = $electronics->relativeQuery()->children()->all();
        $I->assertCount(1, $electronicsChildren);
        $I->assertEquals('Laptops', $electronicsChildren[0]->getName());

        $this->assertTreeIntegrity($I);

        $I->wantTo('merge Lifestyle articles into Technology');

        $lifestyleCategory = TreeNode::query()->where(['name' => 'Lifestyle'])->one();
        $lifestyleArticles = $lifestyleCategory->relativeQuery()->children()->all();

        foreach ($lifestyleArticles as $article) {
            $techCategory->refresh();
            $article->saveInto($techCategory);
        }

        $lifestyleCategory->refresh();
        $lifestyleCategory->delete();

        $techCategory->refresh();
        $techArticles = $techCategory->relativeQuery()->children()->all();
        $I->assertCount(5, $techArticles);

        $this->assertTreeIntegrity($I);

        $I->wantTo('verify final structure with fluent API queries');

        $totalNodes = TreeNode::query()->count();
        $I->assertGreaterThan(15, $totalNodes);

        $roots = TreeNode::query()->roots()->all();
        $I->assertCount(3, $roots);
        $rootNames = array_map(fn($n) => $n->getName(), $roots);
        $I->assertEquals([
            'Homepage',
            'Blog',
            'Products',
        ], $rootNames);

        $homepage = TreeNode::query()->where(['name' => 'Homepage'])->one();
        $homepageTree = $homepage->relativeQuery()->children()->includeDescendants()->includeSelf()->all();
        $I->assertGreaterThan(5, count($homepageTree));

        $servicesReversed = $services->relativeQuery()->children()->reverse()->all();
        $reversedNames = array_map(fn($n) => $n->getName(), $servicesReversed);
        $I->assertEquals(array_reverse([
            'Consulting',
            'Web Development',
            'UI/UX Design',
            'Mobile Development',
        ]), $reversedNames);

        $this->assertTreeIntegrity($I);

        $I->wantTo('test edge cases');

        $about = TreeNode::query()->where(['name' => 'About Us'])->one();
        $aboutOldPath = $about->path;
        $about->saveBefore($homepage);
        $about->refresh();
        $homepage->refresh();
        $I->assertEquals(1, $about->level, 'About should now be at root level');
        $I->assertNotEquals($aboutOldPath, $about->path, 'About path should have changed');

        $services = TreeNode::query()->where(['name' => 'Services'])->one();
        $webDev = TreeNode::query()->where(['name' => 'Web Development'])->one();
        $I->assertFalse($services->canMove($webDev->path));

        $I->assertTrue($about->canMove($blog->path));

        $this->assertTreeIntegrity($I);
    }

    /**
     * Test building a deep tree structure (10+ levels).
     */
    public function testDeepTreeStructure(HazeltreeTraitTester $I): void
    {
        $I->wantTo('build and manipulate a deep tree structure');

        $root = $this->createNode('Level 1');
        $root->save();

        $parent = $root;
        for ($i = 2; $i <= 10; $i++) {
            $node = $this->createNode('Level ' . $i);
            $node->saveInto($parent);
            $parent = $node;
        }

        $deepestNode = TreeNode::query()->where(['name' => 'Level 10'])->one();
        $I->assertEquals(10, $deepestNode->level);

        $ancestors = $deepestNode->relativeQuery()->parent()->includeAncestors()->all();
        $I->assertCount(9, $ancestors);

        $level5 = TreeNode::query()->where(['name' => 'Level 5'])->one();
        $level5->saveInto($root);

        $level5->refresh();
        $I->assertEquals(2, $level5->level);

        $deepestNode->refresh();
        $I->assertEquals(7, $deepestNode->level);

        $this->assertTreeIntegrity($I);
    }

    /**
     * Test concurrent-like operations (multiple operations in sequence).
     */
    public function testMultipleOperationsSequence(HazeltreeTraitTester $I): void
    {
        $I->wantTo('perform multiple operations in sequence without issues');

        $root = $this->createNode('Root');
        $root->save();

        for ($i = 1; $i <= 5; $i++) {
            $child = $this->createNode('Child ' . $i);
            $child->saveInto($root);

            for ($j = 1; $j <= 3; $j++) {
                $grandchild = $this->createNode('Child ' . $i . ' - Sub ' . $j);
                $grandchild->saveInto($child);
            }
        }

        $child1 = TreeNode::query()->where(['name' => 'Child 1'])->one();
        $child5 = TreeNode::query()->where(['name' => 'Child 5'])->one();

        $child1->saveAfter($child5);

        $child3 = TreeNode::query()->where(['name' => 'Child 3'])->one();
        $child2 = TreeNode::query()->where(['name' => 'Child 2'])->one();
        $child3->saveBefore($child2);

        $child4 = TreeNode::query()->where(['name' => 'Child 4'])->one();
        $child4->delete();

        $root->refresh();
        $children = $root->relativeQuery()->children()->all();
        $childNames = array_map(fn($n) => $n->getName(), $children);
        $I->assertEquals([
            'Child 3',
            'Child 2',
            'Child 5',
            'Child 1',
        ], $childNames);

        foreach ($children as $child) {
            $grandchildren = $child->relativeQuery()->children()->all();
            $I->assertCount(3, $grandchildren);
        }

        $this->assertTreeIntegrity($I);
    }

    /**
     * Test with special characters in names.
     */
    public function testSpecialCharactersInNames(HazeltreeTraitTester $I): void
    {
        $I->wantTo('handle special characters in node names');

        $root = $this->createNode('Root <test>');
        $root->save();

        $child1 = $this->createNode("Child with 'quotes'");
        $child1->saveInto($root);

        $child2 = $this->createNode('Child with "double quotes"');
        $child2->saveInto($root);

        $child3 = $this->createNode('Child with émojis');
        $child3->saveInto($root);

        $child4 = $this->createNode('Child/with/slashes');
        $child4->saveInto($root);

        $children = $root->relativeQuery()->children()->all();
        $I->assertCount(4, $children);

        $found = TreeNode::query()->where(['name' => "Child with 'quotes'"])->one();
        $I->assertNotNull($found);

        $this->assertTreeIntegrity($I);
    }

    /**
     * Helper to create a new TreeNode.
     */
    private function createNode(string $name): TreeNode
    {
        $node = new TreeNode();
        $node->setName($name);
        return $node;
    }

    /**
     * Assert that the tree maintains integrity (nested set properties).
     */
    private function assertTreeIntegrity(HazeltreeTraitTester $I): void
    {
        $allNodes = TreeNode::query()->orderBy(['left' => SORT_ASC])->all();

        foreach ($allNodes as $node) {
            $I->assertLessThan(
                $node->right,
                $node->left,
                'Node "' . $node->getName() . '" has invalid left/right: ' . $node->left . ' >= ' . $node->right
            );

            $children = $node->relativeQuery()->children()->includeDescendants()->all();
            foreach ($children as $child) {
                $I->assertGreaterThan(
                    $node->left,
                    $child->left,
                    'Child "' . $child->getName() . '" left (' . $child->left . ') must be > parent "' . $node->getName() . '" left (' . $node->left . ')'
                );
                $I->assertLessThan(
                    $node->right,
                    $child->right,
                    'Child "' . $child->getName() . '" right (' . $child->right . ') must be < parent "' . $node->getName() . '" right (' . $node->right . ')'
                );
            }
        }

        $roots = TreeNode::query()->roots()->all();
        $this->assertNoOverlap($I, $roots);

        foreach ($allNodes as $node) {
            $children = $node->relativeQuery()->children()->all();
            $this->assertNoOverlap($I, $children);
        }
    }

    /**
     * Assert that sibling nodes don't overlap.
     * Note: In Hazeltree, consecutive siblings touch (a.right == b.left), which is valid.
     */
    private function assertNoOverlap(HazeltreeTraitTester $I, array $siblings): void
    {
        for ($i = 0; $i < count($siblings); $i++) {
            for ($j = $i + 1; $j < count($siblings); $j++) {
                $a = $siblings[$i];
                $b = $siblings[$j];

                $aBeforeOrTouchingB = $a->right <= $b->left;
                $bBeforeOrTouchingA = $b->right <= $a->left;

                $I->assertTrue(
                    $aBeforeOrTouchingB || $bBeforeOrTouchingA,
                    'Siblings "' . $a->getName() . '" and "' . $b->getName() . '" overlap: [' . $a->left . ', ' . $a->right . '] vs [' . $b->left . ', ' . $b->right . ']'
                );
            }
        }
    }
}
