<?php

/** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\File;
use Rancoud\Session\Session;
use Rancoud\Session\SessionException;

/**
 * Class SessionTest.
 */
class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        $path = ini_get('session.save_path');
        if (empty($path)) {
            $path = DIRECTORY_SEPARATOR . 'tmp';
        }

        $pattern = $path . DIRECTORY_SEPARATOR . 'sess_*';
        foreach (glob($pattern) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testGetNull(): void
    {
        $value = Session::get('emptykey');
        static::assertNull($value);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testSet(): void
    {
        Session::set('a', 'b');
        $value = Session::get('a');
        static::assertEquals('b', $value);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testHas(): void
    {
        Session::set('a', 'b');

        $value = Session::has('a');
        static::assertTrue($value);

        $value = Session::has('empty');
        static::assertFalse($value);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testHasKeyAndValue(): void
    {
        Session::set('a', 'b');

        $value = Session::hasKeyAndValue('a', 'b');
        static::assertTrue($value);

        $value = Session::has('empty');
        static::assertFalse($value);

        $value = Session::hasKeyAndValue('a', 'empty');
        static::assertFalse($value);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testRemove(): void
    {
        Session::set('a', 'b');

        Session::remove('a');
        $value = Session::get('a');
        static::assertNull($value);

        Session::remove('empty');
        $value = Session::get('empty');
        static::assertNull($value);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testGetAndRemove(): void
    {
        Session::set('a', 'b');

        $value = Session::getAndRemove('a');
        static::assertEquals('b', $value);

        $value = Session::getAndRemove('empty');
        static::assertNull($value);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testStartException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Session already started');

        Session::setReadWrite();
        Session::start();
        Session::start();
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseDefaultDriverWhenAlreadyStartedException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Session already started');

        Session::setReadWrite();
        Session::start();
        Session::useDefaultDriver();
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseFileDriverWhenAlreadyStartedException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Session already started');

        Session::setReadWrite();
        Session::start();
        Session::useFileDriver();
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseCustomDriverWhenAlreadyStartedException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Session already started');

        Session::setReadWrite();
        Session::start();
        Session::useCustomDriver(new File());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseDefaultDriver(): void
    {
        Session::useDefaultDriver();
        Session::start();

        static::assertInstanceOf(\SessionHandler::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseDefaultEncryptionDriver(): void
    {
        Session::useDefaultEncryptionDriver('randomKey');
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\DefaultEncryption::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseFileDriver(): void
    {
        Session::useFileDriver();
        Session::start();

        static::assertInstanceOf(File::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseFileDriverWithPrefix(): void
    {
        $prefix = 'youhou_';
        Session::useFileDriver();
        Session::setPrefixForFile($prefix);
        Session::setReadWrite();
        Session::start(['lazy_write' => '0']);
        $sessionId = Session::getId();
        $path = Session::getOption('save_path');
        Session::commit();

        static::assertInstanceOf(File::class, Session::getDriver());
        static::assertFileExists($path . DIRECTORY_SEPARATOR . $prefix . $sessionId);

        $pattern = $path . DIRECTORY_SEPARATOR . 'youhou_*';
        foreach (glob($pattern) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseFileEncryptionDriver(): void
    {
        Session::useFileEncryptionDriver('randomKey');
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\FileEncryption::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseNewDatabaseDriver(): void
    {
        $params = [
            'driver'   => 'mysql',
            'host'     => 'mysql',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];

        $mysqlHost = getenv('MYSQL_HOST', true);
        $params['host'] = ($mysqlHost !== false) ?  $mysqlHost : '127.0.0.1';

        Session::useNewDatabaseDriver($params);
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\Database::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseNewDatabaseEncryptionDriver(): void
    {
        $params = [
            'driver'   => 'mysql',
            'host'     => 'mysql',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];

        $mysqlHost = getenv('MYSQL_HOST', true);
        $params['host'] = ($mysqlHost !== false) ?  $mysqlHost : '127.0.0.1';

        Session::useNewDatabaseEncryptionDriver($params, 'randomKey');
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\DatabaseEncryption::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Exception
     */
    public function testUseCurrentDatabaseDriver(): void
    {
        $conf = new \Rancoud\Database\Configurator([
            'driver'   => 'mysql',
            'host'     => 'mysql',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);

        $mysqlHost = getenv('MYSQL_HOST', true);
        $conf->setHost(($mysqlHost !== false) ?  $mysqlHost : '127.0.0.1');

        $db = new \Rancoud\Database\Database($conf);
        Session::useCurrentDatabaseDriver($db);
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\Database::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Exception
     */
    public function testUseCurrentDatabaseEncryptionDriver(): void
    {
        $userId = 50;
        $conf = new \Rancoud\Database\Configurator([
            'driver'   => 'mysql',
            'host'     => 'mysql',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);

        $mysqlHost = getenv('MYSQL_HOST', true);
        $conf->setHost(($mysqlHost !== false) ?  $mysqlHost : '127.0.0.1');

        $db = new \Rancoud\Database\Database($conf);
        Session::useCurrentDatabaseEncryptionDriver($db, 'randomKey');
        static::assertInstanceOf(\Rancoud\Session\DatabaseEncryption::class, Session::getDriver());

        Session::setUserIdForDatabase($userId);
        Session::setOption('lazy_write', '0');
        Session::set('a', 'b');
        $sessionId = Session::getId();
        Session::commit();

        $userIdInTable = $db->selectVar('SELECT id_user FROM sessions WHERE id = :id', ['id' => $sessionId]);
        static::assertEquals($userId, $userIdInTable);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseNewRedisDriver(): void
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ?  $redisHost : '127.0.0.1';

        Session::useNewRedisDriver($params);
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\Redis::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseNewRedisEncryptionDriver(): void
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ?  $redisHost : '127.0.0.1';

        Session::useNewRedisEncryptionDriver($params, 'randomKey');
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\RedisEncryption::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseCurrentRedisDriver(): void
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ?  $redisHost : '127.0.0.1';

        $redis = new \Predis\Client($params);
        Session::useCurrentRedisDriver($redis);
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\Redis::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseCurrentRedisEncryptionDriver(): void
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ?  $redisHost : '127.0.0.1';

        $redis = new \Predis\Client($params);
        Session::useCurrentRedisEncryptionDriver($redis, 'randomKey');
        Session::start();

        static::assertInstanceOf(\Rancoud\Session\RedisEncryption::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseCustomDriver(): void
    {
        Session::useCustomDriver(new File());
        Session::start();

        static::assertInstanceOf(File::class, Session::getDriver());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUseEncryptionDriverThrowExceptionWhenMethodIncrorrect(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Method unknowed: incorrect');

        Session::useFileEncryptionDriver('randomKey', 'incorrect');
    }

    //exception quand methode pas bonne dans les encryption

    /**
     * @runInSeparateProcess
     * @throws SessionException
     * @throws \Exception
     */
    public function testSetOption(): void
    {
        $defaultOption = Session::getOption('name');
        static::assertEquals($defaultOption, ini_get('session.name'));

        Session::setOption('name', 'my_custom_name');
        $customOption = Session::getOption('name');

        static::assertEquals('my_custom_name', $customOption);

        Session::start(['name' => 'my_other_name']);

        $customOption = Session::getOption('name');
        static::assertEquals('my_other_name', $customOption);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetOptionThrowException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('Incorrect option: azerty');

        Session::getOption('azerty');
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testGetAll(): void
    {
        $sessionValues = Session::getAll();
        static::assertEmpty($sessionValues);

        Session::set('a', 'b');

        $sessionValues = Session::getAll();
        static::assertEquals(['a' => 'b'], $sessionValues);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testFlash(): void
    {
        $flaKey1 = 'a';
        $flaValue1 = 'b';

        $flaKey2 = 'y';
        $flaValue2 = 'u';

        $flaKey3 = 'my_key';
        $flaValue3 = null;

        $flaKey4 = '10';
        $flaValue4 = 55;

        Session::setFlash($flaKey1, $flaValue1);
        Session::setFlash($flaKey2, $flaValue2);

        static::assertTrue(Session::hasFlash($flaKey1));
        static::assertEquals($flaValue1, Session::getFlash($flaKey1));
        static::assertEquals([$flaKey1 => $flaValue1, $flaKey2 => $flaValue2], Session::getAllFlash());

        Session::start(['lazy_write' => '0']);

        Session::keepFlash([$flaKey2]);
        static::assertEquals(['flash_data' => [$flaKey2 => $flaValue2]], $_SESSION);

        $sessionId = Session::getId();
        Session::commit();

        static::assertEquals([], Session::getAllFlash());

        Session::setId($sessionId);
        Session::setReadWrite();
        Session::start();

        static::assertEmpty($_SESSION);
        static::assertTrue(Session::hasFlash($flaKey2));
        static::assertEquals($flaValue2, Session::getFlash($flaKey2));
        static::assertTrue(Session::hasFlashKeyAndValue($flaKey2, $flaValue2));

        Session::setFlash($flaKey3, $flaValue3);
        Session::setFlash($flaKey4, $flaValue4);

        Session::keepFlash();

        Session::commit();

        static::assertEquals([], Session::getAllFlash());

        Session::start();

        $expectedFlashValues = [$flaKey2 => $flaValue2, $flaKey3 => $flaValue3, $flaKey4 => $flaValue4];
        static::assertEquals($expectedFlashValues, Session::getAllFlash());
        static::assertEmpty($_SESSION);

        Session::removeFlash($flaKey2);
        Session::removeFlash($flaKey3);
        static::assertEquals([$flaKey4 => $flaValue4], Session::getAllFlash());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testRollback(): void
    {
        Session::set('a', 'b');
        Session::commit();

        static::assertTrue(Session::has('a'));
        Session::set('azerty', 'b');
        static::assertTrue(Session::has('azerty'));
        Session::rollback();
        static::assertFalse(Session::has('azerty'));
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testUnsaved(): void
    {
        Session::set('a', 'b');
        Session::commit();

        static::assertTrue(Session::has('a'));
        Session::set('azerty', 'b');
        static::assertTrue(Session::has('azerty'));
        Session::unsaved();
        Session::start();
        static::assertEquals(['a' => 'b'], $_SESSION);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testRegenerate(): void
    {
        Session::set('a', 'v');
        $sessionId = Session::getId();
        $success = Session::regenerate();
        static::assertTrue($success);
        static::assertNotEquals($sessionId, Session::getId());
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testDestroy(): void
    {
        Session::set('a', 'v');
        $sessionId = Session::getId();
        $success = Session::destroy();
        static::assertTrue($success);
        static::assertNotEquals($sessionId, Session::getId());
        static::assertEmpty($_SESSION);
    }

    /**
     * @runInSeparateProcess
     * @throws \Exception
     */
    public function testSetReadOnly(): void
    {
        Session::setReadOnly();
        Session::start();
        static::assertFalse(Session::hasStarted());
    }

    /**
     * @runInSeparateProcess
     * @throws \Rancoud\Database\DatabaseException
     * @throws \Exception
     */
    public function testGc(): void
    {
        $conf = new \Rancoud\Database\Configurator([
            'driver'   => 'mysql',
            'host'     => 'mysql',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);

        $mysqlHost = getenv('MYSQL_HOST', true);
        $conf->setHost(($mysqlHost !== false) ?  $mysqlHost : '127.0.0.1');

        $db = new \Rancoud\Database\Database($conf);
        $db->truncateTables('sessions');

        Session::useCurrentDatabaseDriver($db);
        Session::setReadWrite();
        Session::setOption('lazy_write', '0');
        $baseId = 'siqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Qj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'jklezfoipvfk0lferijkoefzjklgrvefLlnMr';
        Session::setId($baseId . $endId1);
        Session::set('a', 'b');
        Session::commit();

        Session::setId($baseId . $endId2);
        Session::set('b', 'a');
        $sessionId = Session::getId();
        Session::commit();

        sleep(1);

        $sql = 'UPDATE sessions SET last_access = DATE_ADD(NOW(), INTERVAL 50000 SECOND) WHERE id = :id';
        $params = ['id' => $sessionId];
        $db->update($sql, $params);

        Session::setOption('gc_maxlifetime', '1');

        sleep(1);

        Session::gc();

        $count = $db->count('SELECT COUNT(*) FROM sessions');
        static::assertEquals(1, $count);
    }
}
