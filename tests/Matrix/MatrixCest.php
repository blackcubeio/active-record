<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Matrix;

use Blackcube\ActiveRecord\Hazeltree\Helpers\Matrix;
use Blackcube\ActiveRecord\Tests\Support\MatrixTester;

/**
 * Unit tests for Matrix class.
 * Target: 100% coverage of mathematical operations.
 *
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */
class MatrixCest
{

    public function testConstructor(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);

        $I->assertEquals(1, $matrix->a);
        $I->assertEquals(2, $matrix->b);
        $I->assertEquals(3, $matrix->c);
        $I->assertEquals(4, $matrix->d);
    }

    public function testToArray(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);

        $I->assertEquals([
            1,
            2,
            3,
            4,
        ], $matrix->toArray());
    }


    public function testDeterminantStandard(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);

        $I->assertEquals(-2, $matrix->getDeterminant());
    }

    public function testDeterminantIdentityMatrix(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            0,
            0,
            1,
        ]);

        $I->assertEquals(1, $matrix->getDeterminant());
    }

    public function testDeterminantZero(MatrixTester $I): void
    {
        $matrix = new Matrix([
            2,
            4,
            4,
            8,
        ]);

        $I->assertEquals(0, $matrix->getDeterminant());
    }

    public function testDeterminantNegative(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);

        $I->assertEquals(-2, $matrix->getDeterminant());
        $I->assertTrue($matrix->getDeterminant() < 0);
    }

    public function testDeterminantWithFloats(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1.5,
            2.5,
            3.0,
            4.0,
        ]);

        $I->assertEqualsWithDelta(-1.5, $matrix->getDeterminant(), 0.0001);
    }


    public function testMultiplyByPositiveInteger(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->multiply(2);

        $I->assertEquals(2, $result->a);
        $I->assertEquals(4, $result->b);
        $I->assertEquals(6, $result->c);
        $I->assertEquals(8, $result->d);

        $I->assertEquals(1, $matrix->a);
    }

    public function testMultiplyByNegativeInteger(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->multiply(-2);

        $I->assertEquals(-2, $result->a);
        $I->assertEquals(-4, $result->b);
        $I->assertEquals(-6, $result->c);
        $I->assertEquals(-8, $result->d);
    }

    public function testMultiplyByZero(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->multiply(0);

        $I->assertEquals(0, $result->a);
        $I->assertEquals(0, $result->b);
        $I->assertEquals(0, $result->c);
        $I->assertEquals(0, $result->d);
    }

    public function testMultiplyByFloat(MatrixTester $I): void
    {
        $matrix = new Matrix([
            2,
            4,
            6,
            8,
        ]);
        $result = $matrix->multiply(0.5);

        $I->assertEquals(1, $result->a);
        $I->assertEquals(2, $result->b);
        $I->assertEquals(3, $result->c);
        $I->assertEquals(4, $result->d);
    }


    public function testMultiplyByMatrix(MatrixTester $I): void
    {
        $matrix1 = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $matrix2 = new Matrix([
            5,
            6,
            7,
            8,
        ]);

        $result = $matrix1->multiply($matrix2);

        $I->assertEquals(19, $result->a);
        $I->assertEquals(22, $result->b);
        $I->assertEquals(43, $result->c);
        $I->assertEquals(50, $result->d);

        $I->assertEquals(1, $matrix1->a);
    }

    public function testMultiplyByIdentityMatrix(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $identity = new Matrix([
            1,
            0,
            0,
            1,
        ]);

        $result = $matrix->multiply($identity);

        $I->assertEquals(1, $result->a);
        $I->assertEquals(2, $result->b);
        $I->assertEquals(3, $result->c);
        $I->assertEquals(4, $result->d);
    }

    public function testMultiplyByInverseGivesIdentity(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $inverse = $matrix->inverse();

        $result = $matrix->multiply($inverse);

        $I->assertEqualsWithDelta(1, $result->a, 0.0001);
        $I->assertEqualsWithDelta(0, $result->b, 0.0001);
        $I->assertEqualsWithDelta(0, $result->c, 0.0001);
        $I->assertEqualsWithDelta(1, $result->d, 0.0001);
    }

    public function testMultiplyNonCommutative(MatrixTester $I): void
    {
        $a = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $b = new Matrix([
            5,
            6,
            7,
            8,
        ]);

        $ab = $a->multiply($b);
        $ba = $b->multiply($a);

        $I->assertNotEquals($ab->a, $ba->a);
        $I->assertNotEquals($ab->b, $ba->b);
    }


    public function testAdjugate(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->adjugate();

        $I->assertEquals(4, $result->a);
        $I->assertEquals(-2, $result->b);
        $I->assertEquals(-3, $result->c);
        $I->assertEquals(1, $result->d);

        $I->assertEquals(1, $matrix->a);
    }

    public function testDoubleAdjugateEqualsOriginal(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->adjugate()->adjugate();

        $I->assertEquals($matrix->a, $result->a);
        $I->assertEquals($matrix->b, $result->b);
        $I->assertEquals($matrix->c, $result->c);
        $I->assertEquals($matrix->d, $result->d);
    }


    public function testInverse(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->inverse();

        $I->assertEquals(-2, $result->a);
        $I->assertEquals(1, $result->b);
        $I->assertEquals(1.5, $result->c);
        $I->assertEquals(-0.5, $result->d);

        $I->assertEquals(1, $matrix->a);
    }

    public function testInverseOfInverseEqualsOriginal(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->inverse()->inverse();

        $I->assertEqualsWithDelta($matrix->a, $result->a, 0.0001);
        $I->assertEqualsWithDelta($matrix->b, $result->b, 0.0001);
        $I->assertEqualsWithDelta($matrix->c, $result->c, 0.0001);
        $I->assertEqualsWithDelta($matrix->d, $result->d, 0.0001);
    }


    public function testTranspose(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->transpose();

        $I->assertEquals(1, $result->a);
        $I->assertEquals(3, $result->b);
        $I->assertEquals(2, $result->c);
        $I->assertEquals(4, $result->d);

        $I->assertEquals(2, $matrix->b);
    }

    public function testDoubleTransposeEqualsOriginal(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);
        $result = $matrix->transpose()->transpose();

        $I->assertEquals($matrix->a, $result->a);
        $I->assertEquals($matrix->b, $result->b);
        $I->assertEquals($matrix->c, $result->c);
        $I->assertEquals($matrix->d, $result->d);
    }


    public function testFluentInterface(MatrixTester $I): void
    {
        $matrix = new Matrix([
            1,
            2,
            3,
            4,
        ]);

        $result = $matrix->adjugate()->multiply(2);

        $I->assertEquals(1, $matrix->a);

        $I->assertEquals(8, $result->a);
        $I->assertEquals(-4, $result->b);
        $I->assertEquals(-6, $result->c);
        $I->assertEquals(2, $result->d);
    }
}
