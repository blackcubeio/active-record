<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\Formulae;

use Blackcube\ActiveRecord\Tests\Support\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\FormulaeTester;
use Blackcube\ActiveRecord\Tests\Support\TestModel;
use Blackcube\ActiveRecord\Tests\Support\TestScopableQuery;
use Yiisoft\Db\Connection\ConnectionProvider;

class BuildFormulaeCest
{
    use DatabaseCestTrait;

    private static bool $dataInserted = false;

    private function ensureTestData(): void
    {
        if (self::$dataInserted) {
            return;
        }
        $this->db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('hello world hello', true, 'fr')")->execute();
        $this->db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('hello hello hello hello', false, 'en')")->execute();
        $this->db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('no match here', true, 'en')")->execute();
        self::$dataInserted = true;
    }


    public function testBuildFormulaeReturnsExpression(FormulaeTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);

        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $expression = $query->buildFormulae($occurrences, ['title'], [':search' => 'hello']);

        $I->assertInstanceOf(\Yiisoft\Db\Expression\Expression::class, $expression);
    }

    public function testBuildFormulaeCountsOccurrences(FormulaeTester $I): void
    {
        $this->ensureTestData();
        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestScopableQuery::create(TestModel::class);
        $relevance = $query->buildFormulae($occurrences, ['title'], [':search' => 'hello']);

        $results = $query
            ->addSelect([
                '{{%testModels}}.*',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->orderBy(['relevance' => SORT_DESC])
            ->all();

        $I->assertCount(2, $results);
        $I->assertEquals('hello hello hello hello', $results[0]->getTitle());
        $I->assertEquals('hello world hello', $results[1]->getTitle());
    }

    public function testBuildFormulaeNoMatchReturnsEmpty(FormulaeTester $I): void
    {
        $this->ensureTestData();
        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestScopableQuery::create(TestModel::class);
        $relevance = $query->buildFormulae($occurrences, ['title'], [':search' => 'nonexistent']);

        $results = $query
            ->addSelect([
                '{{%testModels}}.*',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->orderBy(['relevance' => SORT_DESC])
            ->all();

        $I->assertCount(0, $results);
    }


    public function testBuildFormulaeMultipleColumns(FormulaeTester $I): void
    {
        $db = ConnectionProvider::get();
        $db->createCommand('DELETE FROM `testModels`')->execute();
        $db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('ab test', true, 'ab ab end')")->execute();
        $db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('ab ab test', false, 'en')")->execute();
        $db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('nothing', true, 'fr')")->execute();

        $occurrences = fn (string $col) =>
            '(CHAR_LENGTH(LOWER(COALESCE(' . $col . ', \'\'))) '
            . '- CHAR_LENGTH(REPLACE(LOWER(COALESCE(' . $col . ', \'\')), LOWER(:search), \'\'))) '
            . '/ CHAR_LENGTH(:search)';

        $query = TestScopableQuery::create(TestModel::class);
        $relevance = $query->buildFormulae($occurrences, [
            'title',
            'languageId',
        ], [':search' => 'ab']);

        $results = $query
            ->addSelect([
                '{{%testModels}}.*',
                'relevance' => $relevance,
            ])
            ->having([
                '>',
                'relevance',
                0,
            ])
            ->orderBy(['relevance' => SORT_DESC])
            ->all();

        $I->assertCount(2, $results);
        $I->assertEquals('ab test', $results[0]->getTitle());
        $I->assertEquals('ab ab test', $results[1]->getTitle());
    }


    public function testBuildFormulaeQualifiesColumns(FormulaeTester $I): void
    {
        $capture = [];
        $spy = function (string $col) use (&$capture) {
            $capture[] = $col;
            return 'CHAR_LENGTH(' . $col . ')';
        };

        $query = TestScopableQuery::create(TestModel::class);
        $query->buildFormulae($spy, [
            'title',
            'languageId',
        ], []);

        $I->assertCount(2, $capture);
        $I->assertStringContainsString('testModels', $capture[0]);
        $I->assertStringContainsString('title', $capture[0]);
        $I->assertStringContainsString('testModels', $capture[1]);
        $I->assertStringContainsString('languageId', $capture[1]);
    }

    public function testBuildFormulaeWithAliasQualifies(FormulaeTester $I): void
    {
        $capture = [];
        $spy = function (string $col) use (&$capture) {
            $capture[] = $col;
            return 'CHAR_LENGTH(' . $col . ')';
        };

        $query = TestScopableQuery::create(TestModel::class);
        $query->alias('t');
        $query->buildFormulae($spy, ['title'], []);

        $I->assertCount(1, $capture);
        $I->assertStringContainsString('t.', $capture[0]);
        $I->assertStringContainsString('title', $capture[0]);
    }
}
