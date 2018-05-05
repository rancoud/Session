<?php

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
    protected function setUp()
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
     */
    public function testGetNull()
    {
        $value = Session::get('emptykey');
        static::assertNull($value);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSet()
    {
        Session::set('a', 'b');
        $value = Session::get('a');
        static::assertEquals('b', $value);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHas()
    {
        Session::set('a', 'b');

        $value = Session::has('a');
        static::assertTrue($value);

        $value = Session::has('empty');
        static::assertFalse($value);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHasKeyAndValue()
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
     */
    public function testRemove()
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
     */
    public function testStartException()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Session already started');
        
        Session::setReadWrite();
        Session::start();
        Session::start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultDriverWhenAlreadyStartedException()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Session already started');
        
        Session::setReadWrite();
        Session::start();
        Session::useDefaultDriver();
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseFileDriverWhenAlreadyStartedException()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Session already started');
        
        Session::setReadWrite();
        Session::start();
        Session::useFileDriver();
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCustomDriverWhenAlreadyStartedException()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Session already started');
        
        Session::setReadWrite();
        Session::start();
        Session::useCustomDriver(new File());
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultDriver()
    {
        Session::useDefaultDriver();
        Session::start();

        static::assertEquals('SessionHandler', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultEncryptionDriver()
    {
        Session::useDefaultEncryptionDriver('randomKey');
        Session::start();

        static::assertEquals('Rancoud\Session\DefaultEncryption', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseFileDriver()
    {
        Session::useFileDriver();
        Session::start();

        static::assertEquals('Rancoud\Session\File', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseFileDriverWithPrefix()
    {
        $prefix = 'youhou_';
        Session::useFileDriver();
        Session::setPrefixForFile($prefix);
        Session::setReadWrite();
        Session::start(['lazy_write' => '0']);
        $sessionId = Session::getId();
        $path = Session::getOption('save_path');
        Session::commit();

        static::assertEquals('Rancoud\Session\File', get_class(Session::getDriver()));
        static::assertTrue(file_exists($path . DIRECTORY_SEPARATOR . $prefix . $sessionId));

        $pattern = $path . DIRECTORY_SEPARATOR . 'youhou_*';
        foreach (glob($pattern) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseFileEncryptionDriver()
    {
        Session::useFileEncryptionDriver('randomKey');
        Session::start();

        static::assertEquals('Rancoud\Session\FileEncryption', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseNewDatabaseDriver()
    {
        $params = [
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];
        Session::useNewDatabaseDriver($params);
        Session::start();

        static::assertEquals('Rancoud\Session\Database', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseNewDatabaseEncryptionDriver()
    {
        $params = [
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];
        Session::useNewDatabaseEncryptionDriver($params, 'randomKey');
        Session::start();

        static::assertEquals('Rancoud\Session\DatabaseEncryption', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCurrentDatabaseDriver()
    {
        $conf = new \Rancoud\Database\Configurator([
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);
        $db = new \Rancoud\Database\Database($conf);
        Session::useCurrentDatabaseDriver($db);
        Session::start();

        static::assertEquals('Rancoud\Session\Database', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCurrentDatabaseEncryptionDriver()
    {
        $userId = 50;
        $conf = new \Rancoud\Database\Configurator([
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);
        $db = new \Rancoud\Database\Database($conf);
        Session::useCurrentDatabaseEncryptionDriver($db, 'randomKey');
        static::assertEquals('Rancoud\Session\DatabaseEncryption', get_class(Session::getDriver()));

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
     */
    public function testUseNewRedisDriver()
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        Session::useNewRedisDriver($params);
        Session::start();

        static::assertEquals('Rancoud\Session\Redis', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseNewRedisEncryptionDriver()
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        Session::useNewRedisEncryptionDriver($params, 'randomKey');
        Session::start();

        static::assertEquals('Rancoud\Session\RedisEncryption', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCurrentRedisDriver()
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        $redis = new \Predis\Client($params);
        Session::useCurrentRedisDriver($redis);
        Session::start();

        static::assertEquals('Rancoud\Session\Redis', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCurrentRedisEncryptionDriver()
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        $redis = new \Predis\Client($params);
        Session::useCurrentRedisEncryptionDriver($redis, 'randomKey');
        Session::start();

        static::assertEquals('Rancoud\Session\RedisEncryption', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCustomDriver()
    {
        Session::useCustomDriver(new File());
        Session::start();

        static::assertEquals('Rancoud\Session\File', get_class(Session::getDriver()));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseEncryptionDriverThrowExceptionWhenMethodIncrorrect()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Method unknowed: incorrect');
        
        Session::useFileEncryptionDriver('randomKey', 'incorrect');
    }

    //exception quand methode pas bonne dans les encryption

    /**
     * @runInSeparateProcess
     */
    public function testSetOption()
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
    public function testSetOptionThrowException()
    {
        static::expectException(SessionException::class);
        static::expectExceptionMessage('Incorrect option: azerty');
        
        Session::getOption('azerty');
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetAll()
    {
        $sessionValues = Session::getAll();
        static::assertTrue(empty($sessionValues));

        Session::set('a', 'b');

        $sessionValues = Session::getAll();
        static::assertEquals(['a' => 'b'], $sessionValues);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFlash()
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

        Session::start(['lazy_write' => '0']);
        Session::keepFlash([$flaKey2]);

        static::assertEquals(['flash_data' => [$flaKey2 => $flaValue2]], $_SESSION);
        $sessionId = Session::getId();
        Session::commit();

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
        Session::start();

        $expectedFlashValues = [$flaKey2 => $flaValue2, $flaKey3 => $flaValue3, $flaKey4 => $flaValue4];
        static::assertEquals($expectedFlashValues, Session::getAllFlash());
        static::assertEmpty($_SESSION);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRollback()
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
     */
    public function testUnsaved()
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
     */
    public function testRegenerate()
    {
        Session::set('a', 'v');
        $sessionId = Session::getId();
        $success = Session::regenerate();
        static::assertTrue($success);
        static::assertNotEquals($sessionId, Session::getId());
    }

    /**
     * @runInSeparateProcess
     */
    public function testDestroy()
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
     */
    public function testSetReadOnly()
    {
        Session::setReadOnly();
        Session::start();
        static::assertFalse(Session::hasStarted());
    }

    /**
     * @runInSeparateProcess
     */
    public function testGc()
    {
        $conf = new \Rancoud\Database\Configurator([
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);
        $db = new \Rancoud\Database\Database($conf);
        $db->truncateTable('sessions');

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
        Session::commit();

        sleep(1);

        $sql = 'update sessions set last_access = DATE_ADD(NOW(), INTERVAL 5000 SECOND) WHERE id = :id';
        $params = ['id' => $baseId . $endId2];
        $db->update($sql, $params);

        Session::setOption('gc_maxlifetime', '1');

        sleep(1);

        Session::gc();

        $count = $db->count('SELECT COUNT(*) FROM sessions');
        static::assertEquals(1, $count);
    }
}
