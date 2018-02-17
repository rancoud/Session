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
        $anonymousClass = new class() extends Session {
            public static $driver;
        };

        $anonymousClass::useDefaultDriver();
        $anonymousClass::start();

        static::assertEquals('SessionHandler', get_class($anonymousClass::$driver));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseFileDriver()
    {
        $anonymousClass = new class() extends Session {
            public static $driver;
        };

        $anonymousClass::useFileDriver();
        $anonymousClass::start();

        static::assertEquals('Rancoud\Session\File', get_class($anonymousClass::$driver));
    }

    /**
     * @runInSeparateProcess
     */
    public function testUseCustomDriver()
    {
        $anonymousClass = new class() extends Session {
            public static $driver;
        };

        $anonymousClass::useCustomDriver(new File());
        $anonymousClass::start();

        static::assertEquals('Rancoud\Session\File', get_class($anonymousClass::$driver));
    }
}
