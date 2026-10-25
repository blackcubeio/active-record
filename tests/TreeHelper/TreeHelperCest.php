<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\TreeHelper;

use Blackcube\ActiveRecord\Hazeltree\Exceptions\InvalidSegmentException;
use Blackcube\ActiveRecord\Hazeltree\Helpers\Matrix;
use Blackcube\ActiveRecord\Hazeltree\Helpers\TreeHelper;
use Blackcube\ActiveRecord\Tests\Support\TreeHelperTester;

class TreeHelperCest
{

    public function testBuildSegmentMatrixForSegment1(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::buildSegmentMatrix(1);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(1, $matrix->b);
        $I->assertEquals(1, $matrix->c);
        $I->assertEquals(2, $matrix->d);
    }

    public function testBuildSegmentMatrixForSegment2(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::buildSegmentMatrix(2);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(1, $matrix->b);
        $I->assertEquals(2, $matrix->c);
        $I->assertEquals(3, $matrix->d);
    }

    public function testBuildSegmentMatrixForSegment5(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::buildSegmentMatrix(5);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(1, $matrix->b);
        $I->assertEquals(5, $matrix->c);
        $I->assertEquals(6, $matrix->d);
    }

    public function testBuildSegmentMatrixThrowsForZero(TreeHelperTester $I): void
    {
        $I->expectThrowable(InvalidSegmentException::class, function () {
            TreeHelper::buildSegmentMatrix(0);
        });
    }

    public function testBuildSegmentMatrixThrowsForNegative(TreeHelperTester $I): void
    {
        $I->expectThrowable(InvalidSegmentException::class, function () {
            TreeHelper::buildSegmentMatrix(-1);
        });
    }


    public function testBuildBumpMatrixWithZeroOffset(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::buildBumpMatrix(0);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(0, $matrix->b);
        $I->assertEquals(0, $matrix->c);
        $I->assertEquals(1, $matrix->d);
    }

    public function testBuildBumpMatrixWithPositiveOffset(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::buildBumpMatrix(3);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(0, $matrix->b);
        $I->assertEquals(3, $matrix->c);
        $I->assertEquals(1, $matrix->d);
    }

    public function testBuildBumpMatrixWithNegativeOffset(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::buildBumpMatrix(-2);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(0, $matrix->b);
        $I->assertEquals(-2, $matrix->c);
        $I->assertEquals(1, $matrix->d);
    }


    public function testConvertSingleSegmentPath(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1');

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(2, $matrix->b);
        $I->assertEquals(1, $matrix->c);
        $I->assertEquals(1, $matrix->d);
    }

    public function testConvertPathToMatrix1(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1');

        $I->assertEquals(1, TreeHelper::getLeft($matrix));
        $I->assertEquals(2, TreeHelper::getRight($matrix));
    }

    public function testConvertPathToMatrix2(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2');

        $I->assertEqualsWithDelta(2, TreeHelper::getLeft($matrix), 0.0001);
        $I->assertEqualsWithDelta(3, TreeHelper::getRight($matrix), 0.0001);
    }

    public function testConvertPathToMatrix1_1(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.1');

        $left = TreeHelper::getLeft($matrix);
        $right = TreeHelper::getRight($matrix);

        $I->assertGreaterThan(1, $left);
        $I->assertLessThan(2, $right);
        $I->assertLessThan($right, $left);
    }

    public function testConvertPathToMatrix1_2(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2');

        $left = TreeHelper::getLeft($matrix);
        $right = TreeHelper::getRight($matrix);

        $I->assertGreaterThan(1, $left);
        $I->assertLessThan(2, $right);
        $I->assertLessThan($right, $left);
    }

    public function testConvertPathToMatrixDeep(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2.3');

        $left = TreeHelper::getLeft($matrix);
        $right = TreeHelper::getRight($matrix);

        $I->assertLessThan($right, $left);
    }


    public function testConvertMatrixToPathRoundTrip1(TreeHelperTester $I): void
    {
        $path = '1';
        $matrix = TreeHelper::convert($path);
        $result = TreeHelper::convert($matrix);

        $I->assertEquals($path, $result);
    }

    public function testConvertMatrixToPathRoundTrip2(TreeHelperTester $I): void
    {
        $path = '2';
        $matrix = TreeHelper::convert($path);
        $result = TreeHelper::convert($matrix);

        $I->assertEquals($path, $result);
    }

    public function testConvertMatrixToPathRoundTrip1_1(TreeHelperTester $I): void
    {
        $path = '1.1';
        $matrix = TreeHelper::convert($path);
        $result = TreeHelper::convert($matrix);

        $I->assertEquals($path, $result);
    }

    public function testConvertMatrixToPathRoundTrip1_2_3(TreeHelperTester $I): void
    {
        $path = '1.2.3';
        $matrix = TreeHelper::convert($path);
        $result = TreeHelper::convert($matrix);

        $I->assertEquals($path, $result);
    }

    public function testConvertMatrixToPathRoundTripDeep(TreeHelperTester $I): void
    {
        $path = '3.1.4.1.5';
        $matrix = TreeHelper::convert($path);
        $result = TreeHelper::convert($matrix);

        $I->assertEquals($path, $result);
    }


    public function testGetLastSegmentFromPathSingle(TreeHelperTester $I): void
    {
        $I->assertEquals(1, TreeHelper::getLastSegment('1'));
        $I->assertEquals(5, TreeHelper::getLastSegment('5'));
    }

    public function testGetLastSegmentFromPathMultiple(TreeHelperTester $I): void
    {
        $I->assertEquals(3, TreeHelper::getLastSegment('1.2.3'));
        $I->assertEquals(7, TreeHelper::getLastSegment('1.2.3.4.5.6.7'));
    }

    public function testGetLastSegmentFromMatrix(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2.3');
        $I->assertEquals(3, TreeHelper::getLastSegment($matrix));
    }

    public function testGetLastSegmentFromMatrixSingle(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('5');
        $I->assertEquals(5, TreeHelper::getLastSegment($matrix));
    }


    public function testGetBasePathFromPathSingle(TreeHelperTester $I): void
    {
        $I->assertEquals('', TreeHelper::getBasePath('1'));
    }

    public function testGetBasePathFromPathMultiple(TreeHelperTester $I): void
    {
        $I->assertEquals('1.2', TreeHelper::getBasePath('1.2.3'));
        $I->assertEquals('1', TreeHelper::getBasePath('1.2'));
    }

    public function testGetBasePathFromMatrix(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2.3');
        $I->assertEquals('1.2', TreeHelper::getBasePath($matrix));
    }


    public function testExtractParentMatrixFromRoot(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1');
        $parent = TreeHelper::getParent($matrix);

        $I->assertNull($parent);
    }

    public function testExtractParentMatrixFromLevel2(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2');
        $parent = TreeHelper::getParent($matrix);

        $I->assertNotNull($parent);
        $parentPath = TreeHelper::convert($parent);
        $I->assertEquals('1', $parentPath);
    }

    public function testExtractParentMatrixFromLevel3(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2.3');
        $parent = TreeHelper::getParent($matrix);

        $I->assertNotNull($parent);
        $parentPath = TreeHelper::convert($parent);
        $I->assertEquals('1.2', $parentPath);
    }


    public function testGetLevelFromPathLevel1(TreeHelperTester $I): void
    {
        $I->assertEquals(1, TreeHelper::getLevel('1'));
        $I->assertEquals(1, TreeHelper::getLevel('5'));
    }

    public function testGetLevelFromPathLevel2(TreeHelperTester $I): void
    {
        $I->assertEquals(2, TreeHelper::getLevel('1.2'));
        $I->assertEquals(2, TreeHelper::getLevel('3.7'));
    }

    public function testGetLevelFromPathDeep(TreeHelperTester $I): void
    {
        $I->assertEquals(5, TreeHelper::getLevel('1.2.3.4.5'));
    }

    public function testGetLevelFromMatrixLevel1(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1');
        $I->assertEquals(1, TreeHelper::getLevel($matrix));
    }

    public function testGetLevelFromMatrixLevel2(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2');
        $I->assertEquals(2, TreeHelper::getLevel($matrix));
    }

    public function testGetLevelFromMatrixDeep(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2.3.4.5');
        $I->assertEquals(5, TreeHelper::getLevel($matrix));
    }


    public function testLeftRightOrdering(TreeHelperTester $I): void
    {
        $paths = [
            '1',
            '2',
            '1.1',
            '1.2',
            '2.1',
            '1.2.3',
        ];

        foreach ($paths as $path) {
            $matrix = TreeHelper::convert($path);
            $left = TreeHelper::getLeft($matrix);
            $right = TreeHelper::getRight($matrix);

            $I->assertLessThan($right, $left, 'Path ' . $path . ': left (' . $left . ') should be less than right (' . $right . ')');
        }
    }

    public function testChildrenNestedWithinParent(TreeHelperTester $I): void
    {
        $parentMatrix = TreeHelper::convert('1');
        $parentLeft = TreeHelper::getLeft($parentMatrix);
        $parentRight = TreeHelper::getRight($parentMatrix);

        $childMatrix = TreeHelper::convert('1.1');
        $childLeft = TreeHelper::getLeft($childMatrix);
        $childRight = TreeHelper::getRight($childMatrix);

        $I->assertGreaterThan($parentLeft, $childLeft);
        $I->assertLessThan($parentRight, $childRight);
    }

    public function testSiblingsDoNotOverlap(TreeHelperTester $I): void
    {
        $sibling1Matrix = TreeHelper::convert('1.1');
        $sibling1Left = TreeHelper::getLeft($sibling1Matrix);
        $sibling1Right = TreeHelper::getRight($sibling1Matrix);

        $sibling2Matrix = TreeHelper::convert('1.2');
        $sibling2Left = TreeHelper::getLeft($sibling2Matrix);
        $sibling2Right = TreeHelper::getRight($sibling2Matrix);

        $noOverlap = ($sibling1Right <= $sibling2Left) || ($sibling2Right <= $sibling1Left);
        $I->assertTrue($noOverlap, 'Siblings should not overlap');
    }


    public function testBuildMoveMatrixNoBump(TreeHelperTester $I): void
    {
        $fromMatrix = TreeHelper::convert('1.1');
        $toMatrix = TreeHelper::convert('2.1');

        $moveMatrix = TreeHelper::buildMoveMatrix($fromMatrix, $toMatrix, 0);

        $I->assertInstanceOf(Matrix::class, $moveMatrix);
    }

    public function testBuildMoveMatrixWithBump(TreeHelperTester $I): void
    {
        $fromMatrix = TreeHelper::convert('1.1');
        $toMatrix = TreeHelper::convert('2.1');

        $moveMatrix = TreeHelper::buildMoveMatrix($fromMatrix, $toMatrix, 1);

        $I->assertInstanceOf(Matrix::class, $moveMatrix);
    }


    public function testPathSeparatorConstant(TreeHelperTester $I): void
    {
        $I->assertEquals('.', TreeHelper::PATH_SEPARATOR);
    }


    public function testGetRootMatrixReturnsSwapMatrix(TreeHelperTester $I): void
    {
        $root = TreeHelper::getRootMatrix();

        $I->assertEquals(0, $root->a);
        $I->assertEquals(1, $root->b);
        $I->assertEquals(1, $root->c);
        $I->assertEquals(0, $root->d);
    }

    public function testGetRootMatrixIsNotIdentity(TreeHelperTester $I): void
    {
        $root = TreeHelper::getRootMatrix();

        $I->assertFalse(
            $root->a === 1 && $root->b === 0 && $root->c === 0 && $root->d === 1,
            'Root matrix must not be identity'
        );
    }

    public function testGetRootMatrixDeterminantIsMinusOne(TreeHelperTester $I): void
    {
        $root = TreeHelper::getRootMatrix();
        $I->assertEquals(-1, $root->getDeterminant());
    }


    public function testGetParentWithPathStringRoot(TreeHelperTester $I): void
    {
        $parent = TreeHelper::getParent('1');
        $I->assertNull($parent);
    }

    public function testGetParentWithPathStringLevel2(TreeHelperTester $I): void
    {
        $parent = TreeHelper::getParent('1.2');
        $I->assertNotNull($parent);
        $I->assertEquals('1', TreeHelper::convert($parent));
    }

    public function testGetParentWithPathStringLevel3(TreeHelperTester $I): void
    {
        $parent = TreeHelper::getParent('2.4.3');
        $I->assertNotNull($parent);
        $I->assertEquals('2.4', TreeHelper::convert($parent));
    }

    public function testGetParentWithPathStringDeep(TreeHelperTester $I): void
    {
        $parent = TreeHelper::getParent('1.2.3.4.5');
        $I->assertNotNull($parent);
        $I->assertEquals('1.2.3.4', TreeHelper::convert($parent));
    }


    public function testGetLeftWithPathString(TreeHelperTester $I): void
    {
        $left = TreeHelper::getLeft('1');
        $I->assertEquals(1.0, $left);
    }

    public function testGetRightWithPathString(TreeHelperTester $I): void
    {
        $right = TreeHelper::getRight('1');
        $I->assertEquals(2.0, $right);
    }

    public function testGetLeftRightWithPathStringPath2(TreeHelperTester $I): void
    {
        $I->assertEquals(2.0, TreeHelper::getLeft('2'));
        $I->assertEquals(3.0, TreeHelper::getRight('2'));
    }

    public function testGetLeftRightWithPathStringDeep(TreeHelperTester $I): void
    {
        $left = TreeHelper::getLeft('1.2.3');
        $right = TreeHelper::getRight('1.2.3');

        $I->assertLessThan($right, $left);
        $I->assertGreaterThan(1.0, $left);
        $I->assertLessThan(2.0, $right);
    }


    public function testPaperFigure3Path2Values(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2');

        $I->assertEquals(2, $matrix->a, 'nv should be 2');
        $I->assertEquals(3, $matrix->b, 'snv should be 3');
        $I->assertEquals(1, $matrix->c, 'dv should be 1');
        $I->assertEquals(1, $matrix->d, 'sdv should be 1');
    }

    public function testPaperFigure3Path243Values(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2.4.3');

        $I->assertEquals(65, $matrix->a, 'nv should be 65');
        $I->assertEquals(82, $matrix->b, 'snv should be 82');
        $I->assertEquals(23, $matrix->c, 'dv should be 23');
        $I->assertEquals(29, $matrix->d, 'sdv should be 29');
    }

    public function testPaperFigure3Path243LeftRight(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2.4.3');

        $I->assertEqualsWithDelta(65 / 23, TreeHelper::getLeft($matrix), 0.0001);
        $I->assertEqualsWithDelta(82 / 29, TreeHelper::getRight($matrix), 0.0001);
    }

    public function testDeterminantAlwaysMinusOneForAllPaths(TreeHelperTester $I): void
    {
        $paths = [
            '1',
            '2',
            '3',
            '10',
            '1.1',
            '1.2',
            '2.4',
            '1.2.3',
            '2.4.3',
            '1.2.3.4.5.6.7.8.9.10',
        ];

        foreach ($paths as $path) {
            $matrix = TreeHelper::convert($path);
            $I->assertEquals(
                -1,
                $matrix->getDeterminant(),
                'Determinant for path \'' . $path . '\' should be -1'
            );
        }
    }

    public function testMatrixMultiplicationChain(TreeHelperTester $I): void
    {
        $root = TreeHelper::getRootMatrix();
        $s2 = TreeHelper::buildSegmentMatrix(2);
        $s4 = TreeHelper::buildSegmentMatrix(4);
        $s3 = TreeHelper::buildSegmentMatrix(3);

        $result = $root->multiply($s2)->multiply($s4)->multiply($s3);
        $expected = TreeHelper::convert('2.4.3');

        $I->assertEquals($expected->a, $result->a);
        $I->assertEquals($expected->b, $result->b);
        $I->assertEquals($expected->c, $result->c);
        $I->assertEquals($expected->d, $result->d);
    }


    public function testGetAncestorMatricesForRootReturnsEmpty(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1');
        $ancestors = TreeHelper::getAncestorMatrices($matrix->a, $matrix->c);

        $I->assertEmpty($ancestors);
    }

    public function testGetAncestorMatricesForLevel2ReturnsOne(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2.4');
        $ancestors = TreeHelper::getAncestorMatrices($matrix->a, $matrix->c);

        $I->assertCount(1, $ancestors);
        $I->assertEquals('2', TreeHelper::convert($ancestors[0]));
    }

    public function testGetAncestorMatricesForLevel3ReturnsTwo(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2.4.3');
        $ancestors = TreeHelper::getAncestorMatrices($matrix->a, $matrix->c);

        $I->assertCount(2, $ancestors);
        $I->assertEquals('2', TreeHelper::convert($ancestors[0]));
        $I->assertEquals('2.4', TreeHelper::convert($ancestors[1]));
    }

    public function testGetAncestorMatricesOrderIsRootToParent(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('1.2.3.4');
        $ancestors = TreeHelper::getAncestorMatrices($matrix->a, $matrix->c);

        $I->assertCount(3, $ancestors);
        $I->assertEquals('1', TreeHelper::convert($ancestors[0]));
        $I->assertEquals('1.2', TreeHelper::convert($ancestors[1]));
        $I->assertEquals('1.2.3', TreeHelper::convert($ancestors[2]));
    }


    public function testGetAncestorPathsForRootReturnsEmpty(TreeHelperTester $I): void
    {
        $ancestors = TreeHelper::getAncestorPaths('1');
        $I->assertEmpty($ancestors);
    }

    public function testGetAncestorPathsForLevel2(TreeHelperTester $I): void
    {
        $ancestors = TreeHelper::getAncestorPaths('2.4');
        $I->assertEquals(['2'], $ancestors);
    }

    public function testGetAncestorPathsForLevel3(TreeHelperTester $I): void
    {
        $ancestors = TreeHelper::getAncestorPaths('2.4.3');
        $I->assertEquals([
            '2',
            '2.4',
        ], $ancestors);
    }

    public function testGetAncestorPathsForDeepPath(TreeHelperTester $I): void
    {
        $ancestors = TreeHelper::getAncestorPaths('1.2.3.4.5');
        $I->assertEquals([
            '1',
            '1.2',
            '1.2.3',
            '1.2.3.4',
        ], $ancestors);
    }

    public function testGetAncestorPathsWithLargeSegments(TreeHelperTester $I): void
    {
        $ancestors = TreeHelper::getAncestorPaths('10.20.30');
        $I->assertEquals([
            '10',
            '10.20',
        ], $ancestors);
    }


    public function testIsAncestorOfReturnsTrueForDirectParent(TreeHelperTester $I): void
    {
        $I->assertTrue(TreeHelper::isAncestorOf('2.4', '2.4.3'));
    }

    public function testIsAncestorOfReturnsTrueForGrandparent(TreeHelperTester $I): void
    {
        $I->assertTrue(TreeHelper::isAncestorOf('2', '2.4.3'));
    }

    public function testIsAncestorOfReturnsTrueForRoot(TreeHelperTester $I): void
    {
        $I->assertTrue(TreeHelper::isAncestorOf('1', '1.2.3.4.5'));
    }

    public function testIsAncestorOfReturnsFalseForSameNode(TreeHelperTester $I): void
    {
        $I->assertFalse(TreeHelper::isAncestorOf('2.4.3', '2.4.3'));
    }

    public function testIsAncestorOfReturnsFalseForDescendant(TreeHelperTester $I): void
    {
        $I->assertFalse(TreeHelper::isAncestorOf('2.4.3', '2.4'));
    }

    public function testIsAncestorOfReturnsFalseForDifferentBranch(TreeHelperTester $I): void
    {
        $I->assertFalse(TreeHelper::isAncestorOf('1', '2.4.3'));
        $I->assertFalse(TreeHelper::isAncestorOf('1.1', '1.2.3'));
    }

    public function testIsAncestorOfReturnsFalseForSibling(TreeHelperTester $I): void
    {
        $I->assertFalse(TreeHelper::isAncestorOf('1.1', '1.2'));
        $I->assertFalse(TreeHelper::isAncestorOf('1.2', '1.1'));
    }


    public function testBuildMoveMatrixSameParentNoBump(TreeHelperTester $I): void
    {
        $parent = TreeHelper::convert('1');
        $moveMatrix = TreeHelper::buildMoveMatrix($parent, $parent, 0);

        $I->assertInstanceOf(Matrix::class, $moveMatrix);
    }

    public function testBuildMoveMatrixPreservesDeterminant(TreeHelperTester $I): void
    {
        $from = TreeHelper::convert('1');
        $to = TreeHelper::convert('2');
        $moveMatrix = TreeHelper::buildMoveMatrix($from, $to, 1);

        $det = $moveMatrix->getDeterminant();
        $I->assertEquals(1, $det);
    }

    public function testBuildMoveMatrixFormula(TreeHelperTester $I): void
    {
        $fromParent = TreeHelper::convert('1.1');
        $toParent = TreeHelper::convert('2.1');

        $moveMatrix = TreeHelper::buildMoveMatrix($fromParent, $toParent, 0);

        $bumpMatrix = TreeHelper::buildBumpMatrix(0);
        $fromInverse = $fromParent->inverse();
        $expected = $toParent->multiply($bumpMatrix)->multiply($fromInverse);

        $I->assertEquals($expected->a, $moveMatrix->a);
        $I->assertEquals($expected->b, $moveMatrix->b);
        $I->assertEquals($expected->c, $moveMatrix->c);
        $I->assertEquals($expected->d, $moveMatrix->d);
    }

    public function testBuildMoveMatrixWithBumpFormula(TreeHelperTester $I): void
    {
        $parent = TreeHelper::convert('1.1');

        $moveMatrix = TreeHelper::buildMoveMatrix($parent, $parent, 2);

        $bumpMatrix = TreeHelper::buildBumpMatrix(2);
        $parentInverse = $parent->inverse();
        $expected = $parent->multiply($bumpMatrix)->multiply($parentInverse);

        $I->assertEquals($expected->a, $moveMatrix->a);
        $I->assertEquals($expected->b, $moveMatrix->b);
        $I->assertEquals($expected->c, $moveMatrix->c);
        $I->assertEquals($expected->d, $moveMatrix->d);
    }

    public function testBuildMoveMatrixDeterminantIsOne(TreeHelperTester $I): void
    {
        $testCases = [
            [
                'from' => '1.1',
                'to' => '2.1',
                'bump' => 0,
            ],
            [
                'from' => '1.1',
                'to' => '1.1',
                'bump' => 2,
            ],
            [
                'from' => '1.1',
                'to' => '1.1',
                'bump' => -1,
            ],
            [
                'from' => '1.2.3',
                'to' => '2.1',
                'bump' => 5,
            ],
        ];

        foreach ($testCases as $case) {
            $from = TreeHelper::convert($case['from']);
            $to = TreeHelper::convert($case['to']);
            $moveMatrix = TreeHelper::buildMoveMatrix($from, $to, $case['bump']);

            $I->assertEquals(
                1,
                $moveMatrix->getDeterminant(),
                'Move matrix det should be 1 for ' . $case['from'] . ' -> ' . $case['to'] . ' bump ' . $case['bump']
            );
        }
    }

    public function testBuildMoveMatrixIntegerArithmetic(TreeHelperTester $I): void
    {
        $from = TreeHelper::convert('1.2.3');
        $to = TreeHelper::convert('2.1.4');
        $moveMatrix = TreeHelper::buildMoveMatrix($from, $to, 2);

        $I->assertIsInt($moveMatrix->a);
        $I->assertIsInt($moveMatrix->b);
        $I->assertIsInt($moveMatrix->c);
        $I->assertIsInt($moveMatrix->d);
    }


    public function testDeepPathRoundTrip(TreeHelperTester $I): void
    {
        $path = '1.2.3.4.5.6.7.8.9.10';
        $matrix = TreeHelper::convert($path);
        $result = TreeHelper::convert($matrix);

        $I->assertEquals($path, $result);
    }

    public function testLargeSegmentValues(TreeHelperTester $I): void
    {
        $path = '100.200.300';
        $matrix = TreeHelper::convert($path);

        $I->assertEquals(-1, $matrix->getDeterminant());
        $I->assertGreaterThan(0, $matrix->a);
        $I->assertGreaterThan(0, $matrix->c);

        $I->assertEquals($path, TreeHelper::convert($matrix));
    }

    public function testNestedSetPropertiesHold(TreeHelperTester $I): void
    {
        $paths = [
            '1',
            '1.1',
            '1.2',
            '1.1.1',
            '1.1.2',
            '1.2.1',
            '2',
            '2.1',
        ];

        foreach ($paths as $path) {
            $matrix = TreeHelper::convert($path);
            $left = TreeHelper::getLeft($matrix);
            $right = TreeHelper::getRight($matrix);

            $I->assertLessThan($right, $left, 'Path ' . $path . ': left < right');

            $parent = TreeHelper::getParent($path);
            if ($parent !== null) {
                $parentLeft = TreeHelper::getLeft($parent);
                $parentRight = TreeHelper::getRight($parent);
                $I->assertGreaterThan($parentLeft, $left, 'Path ' . $path . ': left > parent.left');
                $I->assertLessThan($parentRight, $right, 'Path ' . $path . ': right < parent.right');
            }
        }
    }

    public function testSiblingsAreContiguousOrTouching(TreeHelperTester $I): void
    {
        $siblings = [
            '1.1',
            '1.2',
            '1.3',
        ];

        for ($i = 0; $i < count($siblings) - 1; $i++) {
            $current = TreeHelper::convert($siblings[$i]);
            $next = TreeHelper::convert($siblings[$i + 1]);

            $currentRight = TreeHelper::getRight($current);
            $nextLeft = TreeHelper::getLeft($next);

            $I->assertEqualsWithDelta(
                $currentRight,
                $nextLeft,
                0.0001,
                'Sibling ' . $siblings[$i] . ' right should equal ' . $siblings[$i + 1] . ' left'
            );
        }
    }

    public function testIntegerArithmeticPreserved(TreeHelperTester $I): void
    {
        $paths = [
            '1',
            '2.4.3',
            '1.2.3.4.5',
            '10.20.30',
        ];

        foreach ($paths as $path) {
            $matrix = TreeHelper::convert($path);

            $I->assertIsInt($matrix->a, 'Path ' . $path . ': a should be int');
            $I->assertIsInt($matrix->b, 'Path ' . $path . ': b should be int');
            $I->assertIsInt($matrix->c, 'Path ' . $path . ': c should be int');
            $I->assertIsInt($matrix->d, 'Path ' . $path . ': d should be int');
        }
    }


    public function testInverseIsIntegerArithmetic(TreeHelperTester $I): void
    {
        $matrix = TreeHelper::convert('2.4.3');
        $inverse = $matrix->inverse();

        $I->assertIsInt($inverse->a);
        $I->assertIsInt($inverse->b);
        $I->assertIsInt($inverse->c);
        $I->assertIsInt($inverse->d);

        $product = $matrix->multiply($inverse);
        $I->assertEquals(1, $product->a, 'Product a should be 1');
        $I->assertEquals(0, $product->b, 'Product b should be 0');
        $I->assertEquals(0, $product->c, 'Product c should be 0');
        $I->assertEquals(1, $product->d, 'Product d should be 1');
    }


    public function testGetParentReturnsNullForInvalidMatrix(TreeHelperTester $I): void
    {

        $invalidMatrix = new Matrix([
            1,
            2,
            0,
            1,
        ]);
        $parent = TreeHelper::getParent($invalidMatrix);
        $I->assertNull($parent, 'Should return null when c <= 0');

        $invalidMatrix2 = new Matrix([
            1,
            2,
            1,
            0,
        ]);
        $parent2 = TreeHelper::getParent($invalidMatrix2);
        $I->assertNull($parent2, 'Should return null when d <= 0');

        $invalidMatrix3 = new Matrix([
            1,
            2,
            -1,
            1,
        ]);
        $parent3 = TreeHelper::getParent($invalidMatrix3);
        $I->assertNull($parent3, 'Should return null when c < 0');

        $invalidMatrix4 = new Matrix([
            1,
            2,
            1,
            -1,
        ]);
        $parent4 = TreeHelper::getParent($invalidMatrix4);
        $I->assertNull($parent4, 'Should return null when d < 0');
    }

    public function testGetParentReturnsNullWhenParentAIsInvalid(TreeHelperTester $I): void
    {

        $rootMatrix = TreeHelper::getRootMatrix();
        $parent = TreeHelper::getParent($rootMatrix);
        $I->assertNull($parent, 'Root matrix should have no parent');
    }
}
