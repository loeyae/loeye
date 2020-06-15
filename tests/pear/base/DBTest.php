<?php
/**
 * DBTest.php
 *
 * PHP version 7
 *
 * Licensed under the Apache License, Version 2.0 (the "License"),
 * see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
 *
 * @category PHP
 * @package  LOEYE
 * @author   Zhang Yi <loeyae@gmail.com>
 * @version  2020/4/20 14:32
 * @link     https://github.com/loeyae/loeye.git
 */

namespace loeye\unit\base;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\QueryBuilder;
use loeye\base\AppConfig;
use loeye\base\DB;
use loeye\base\Exception;
use loeye\Centra;
use loeye\models\repository\TestRepository;
use loeye\unit\TestCase;
use loeye\models\entity\Test;

class DBTest extends TestCase
{

    protected function setUp()
    {
        Centra::init();
        parent::setUp();
    }

    protected function tearDown()
    {
        $db = DB::init();
        try {
            $qb = $db->createNativeQuery('DROP TABLE test');
            $qb->execute();
        } catch (\Exception $e) {

        }
        parent::tearDown();
    }

    /**
     * @covers \loeye\base\DB
     * @covers \loeye\database\EntityManager
     */
    public function testInstance()
    {
        $db = DB::init();
        $db1 = DB::init();
        $this->assertNotSame($db, $db1);
        $db2 = new DB();
        $this->assertNotSame($db, $db2);
        $db3 = DB::init('default');
        $this->assertNotSame($db, $db3);
    }

    /**
     * @covers \loeye\base\DB
     * @expectedException \loeye\error\BusinessException
     * @expectedExceptionMessage 无效的数据库设置
     */
    public function testInstanceWithNoExistsType()
    {
        $db = DB::init('mysql');
    }

    /**
     * @covers \loeye\base\DB
     * @expectedException \loeye\error\BusinessException
     * @expectedExceptionMessage 无效的数据库类型
     */
    public function testInstancewthBlankType()
    {
        $db = DB::init('');
    }

    /**
     * @covers \loeye\base\DB
     * @covers \loeye\database\EntityManager
     */
    public function testEntityManager()
    {
        $db = DB::init();
        $em = $db->em();
        $entityManager = $db->entityManager();
        $this->assertSame($entityManager, $em);
        $this->assertInstanceOf(EntityManager::class, $em);
    }

    /**
     * @covers \loeye\base\DB
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Throwable
     */
    public function testQueryBuilder()
    {
        $db = DB::init();
        $queryBuilder = $db->createQueryBuilder();
        $qb = $db->qb();
        $this->assertEquals($qb, $queryBuilder);
        $this->assertInstanceOf(QueryBuilder::class, $queryBuilder);
        $nativeQueryBuilder = $db->createNativeQuery('SELECT * FROM test');
        $this->assertInstanceOf(NativeQuery::class, $nativeQueryBuilder);
    }

    /**
     * @return DB
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Throwable
     */
    private function initDB()
    {
        $_ENV['LOEYE_PROFILE_ACTIVE'] = 'dev';
        Centra::$appConfig = new AppConfig();
        $db = DB::init();
        $qb = $db->createNativeQuery('CREATE TABLE test (
            `id` int(10) NOT NULL,
            `name` varchar(64) NOT NULL,
            `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $qb->execute();
        return $db;
    }

    /**
     * @covers \loeye\base\DB
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Throwable
     */
    public function testQuery()
    {
        $db = $this->initDB();
        $result = $db->query('SELECT * FROM test');
        $this->assertEmpty($result);
    }

    /**
     * @covers \loeye\base\DB
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Throwable
     */
    public function testRepository()
    {
        $db = DB::init();
        $object = $db->repository(Test::class);
        $this->assertInstanceOf(TestRepository::class, $object);
    }

    /**
     * @covers \loeye\base\DB
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Throwable
     */
    public function testDatabaseOperation()
    {
        $db = $this->initDB();
        $test = new Test();
        $test->setId(1);
        $test->setName('test');
        $ret = $db->save($test);
        $this->assertSame($test, $ret);
        $entity = $db->entity(Test::class, 1);
        $this->assertSame($test, $entity);
        $this->assertInstanceOf(Test::class, $entity);
        $this->assertEquals($test->getId(), $entity->getId());
        $this->assertEquals($test->getName(), $entity->getName());
        $this->assertInstanceOf(\DateTime::class, $entity->getCreateTime());
        $one = $db->one(Test::class, ['id' => 1]);
        $this->assertSame($entity, $one);
        $one->setName('testNew');
        $db->save($one);
        $db->refresh($test);
        $this->assertSame($entity, $one);
        $this->assertEquals($test->getName(), $one->getName());
        $deleted = $db->remove($one);
        $this->assertTrue($deleted);
        $one = $db->one(Test::class, ['id' => 1]);
        $this->assertNull($one);
    }

}
