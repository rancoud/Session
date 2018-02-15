<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Rancoud\Session\Session;

/**
 * Class SessionTest.
 */
class SessionTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testGetNullSession()
    {
        $value = Session::get('emptykey');
        static::assertNull($value);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetSession()
    {
        Session::set('a', 'b');
        $value = Session::get('a');
        static::assertEquals('b', $value);
    }

    /**
     * @runInSeparateProcess
     */
    public function testHasSession()
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
    public function testHasKeyAndValueSession()
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
    public function testRemoveSession()
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
    public function testStartExceptionSession()
    {
        static::expectException(Exception::class);
        Session::start();
        Session::start();
    }
}
