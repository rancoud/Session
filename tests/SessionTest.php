<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Rancoud\Session\File;
use Rancoud\Session\Session;

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
        static::expectException(Exception::class);
        Session::start();
        Session::start();
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseDefaultDriverWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::useDefaultDriver();
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseFileDriverWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::useFileDriver();
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCustomDriverWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::useCustomDriver(new File());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetNameWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::setName('test');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetSavePathWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::setSavePath('test');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetCookieDomainWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::setCookieDomain('test');
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetLifetimeWhenAlreadyStartedException()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::setLifetime(0);
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
        $conf = new \Rancoud\Database\Configurator([
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);
        $db = new \Rancoud\Database\Database($conf);
        Session::useCurrentDatabaseEncryptionDriver($db, 'randomKey');
        Session::start();

        static::assertEquals('Rancoud\Session\DatabaseEncryption', get_class(Session::getDriver()));
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
        static::expectException(Exception::class);
        Session::useFileEncryptionDriver('randomKey', 'incorrect');
    }

    //exception quand methode pas bonne dans les encryption
}
