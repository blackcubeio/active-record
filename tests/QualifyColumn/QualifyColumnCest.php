<?php

declare(strict_types=1);

/**
 * @copyright 2010-2026 Blackcube - Philippe Gaultier
 * @license https://www.blackcube.io/license
 * @link https://www.blackcube.io
 */

namespace Blackcube\ActiveRecord\Tests\QualifyColumn;

use Blackcube\ActiveRecord\Tests\Support\DatabaseCestTrait;
use Blackcube\ActiveRecord\Tests\Support\QualifyColumnTester;
use Blackcube\ActiveRecord\Tests\Support\TestActiveScope;
use Blackcube\ActiveRecord\Tests\Support\TestModel;
use Blackcube\ActiveRecord\Tests\Support\TestScopableQuery;
use Yiisoft\Db\Connection\ConnectionProvider;

class QualifyColumnCest
{
    use DatabaseCestTrait;

    private static bool $dataInserted = false;

    private function ensureTestData(): void
    {
        if (self::$dataInserted) {
            return;
        }
        $this->insertTestData();
        self::$dataInserted = true;
    }

    private function insertTestData(): void
    {
        $db = ConnectionProvider::get();

        $db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('Model A', true, 'fr')")->execute();
        $db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('Model B', false, 'en')")->execute();
        $db->createCommand("INSERT INTO `testModels` (`title`, `active`, `languageId`) VALUES ('Model C', true, 'en')")->execute();

        $db->createCommand("INSERT INTO `testJoinModels` (`testModelId`, `label`, `active`) VALUES (1, 'Join A1', true)")->execute();
        $db->createCommand("INSERT INTO `testJoinModels` (`testModelId`, `label`, `active`) VALUES (1, 'Join A2', false)")->execute();
        $db->createCommand("INSERT INTO `testJoinModels` (`testModelId`, `label`, `active`) VALUES (2, 'Join B1', true)")->execute();
        $db->createCommand("INSERT INTO `testJoinModels` (`testModelId`, `label`, `active`) VALUES (3, 'Join C1', true)")->execute();
    }


    public function testHashConditionQualified(QualifyColumnTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $results = $query->andWhere(['active' => true])->all();
        $I->assertCount(2, $results);

        $command = TestScopableQuery::create(TestModel::class)
            ->andWhere(['active' => true])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringContainsString('`testModels`.`active`', $sql);
    }

    public function testOperatorConditionQualified(QualifyColumnTester $I): void
    {
        $command = TestScopableQuery::create(TestModel::class)
            ->andWhere([
                'like',
                'title',
                'Model',
            ])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringContainsString('`testModels`.`title`', $sql);
    }

    public function testAlreadyQualifiedNotDoubled(QualifyColumnTester $I): void
    {
        $command = TestScopableQuery::create(TestModel::class)
            ->andWhere(['{{%testModels}}.[[active]]' => true])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringNotContainsString('`testModels`.`testModels`', $sql);
    }


    public function testNestedAndOrConditionsQualified(QualifyColumnTester $I): void
    {
        $command = TestScopableQuery::create(TestModel::class)
            ->andWhere([
                'or',
                ['active' => true],
                ['languageId' => 'fr'],
            ])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringContainsString('`testModels`.`active`', $sql);
        $I->assertStringContainsString('`testModels`.`languageId`', $sql);
    }


    public function testAliasChangesQualifier(QualifyColumnTester $I): void
    {
        $command = TestScopableQuery::create(TestModel::class)
            ->alias('t')
            ->andWhere(['active' => true])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringContainsString('t.`active`', $sql);
    }


    public function testJoinQualificationPreventsAmbiguity(QualifyColumnTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->innerJoin('testJoinModels', '`testJoinModels`.`testModelId` = `testModels`.`id`');
        $query->addScope(TestActiveScope::class);

        $results = $query->active()->all();

        $I->assertCount(2, $results);
    }


    public function testWhereQualifies(QualifyColumnTester $I): void
    {
        $command = TestScopableQuery::create(TestModel::class)
            ->where(['active' => true])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringContainsString('`testModels`.`active`', $sql);
    }

    public function testOrWhereQualifies(QualifyColumnTester $I): void
    {
        $command = TestScopableQuery::create(TestModel::class)
            ->andWhere(['active' => true])
            ->orWhere(['languageId' => 'fr'])
            ->createCommand();
        $sql = $command->getRawSql();
        $I->assertStringContainsString('`testModels`.`languageId`', $sql);
    }


    public function testScopeThroughQualifiedQuery(QualifyColumnTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->addScope(TestActiveScope::class);
        $results = $query->active(active: true)->all();

        $I->assertCount(2, $results);
        foreach ($results as $model) {
            $I->assertTrue($model->isActive());
        }
    }

    public function testScopeWithAliasedQuery(QualifyColumnTester $I): void
    {
        $this->ensureTestData();
        $query = TestScopableQuery::create(TestModel::class);
        $query->alias('t');
        $query->addScope(TestActiveScope::class);
        $results = $query->active(active: false)->all();

        $I->assertCount(1, $results);
        $I->assertFalse($results[0]->isActive());
    }
}
