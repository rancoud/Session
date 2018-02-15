<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\File;

/**
 * Class SessionTest.
 */
class FileTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        rmdir(__DIR__ . '/toto');
    }

    public static function setUpAfterClass()
    {
        rmdir(__DIR__ . '/toto');
    }

    public function testConstruct()
    {
        new File();
        static::assertTrue(true);
    }

    public function testOpen()
    {
        $file = new File();
        $savePath = __DIR__;
        $sessionName = '';
        $success = $file->open($savePath, $sessionName);
        static::assertTrue($success);

        $success = $file->open($savePath . '/toto', $sessionName);
        static::assertTrue($success);
        $success = file_exists($savePath . '/toto');
        static::assertTrue($success);
    }

    public function testClose()
    {
        $file = new File();
        $success = $file->close();
        static::assertTrue($success);
    }

    public function testWrite()
    {
        $file = new File();
        $sessionId = 'test';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);
    }

    public function testRead()
    {
        $file = new File();
        $sessionId = 'test';
        $data = $file->read($sessionId);
        static::assertTrue(!empty($data));
        static::assertTrue(is_string($data));

        $sessionId = '';
        $data = $file->read($sessionId);
        static::assertTrue(empty($data));
        static::assertTrue(is_string($data));
    }

    public function testDestroy()
    {
        $file = new File();
        $sessionId = 'todelete';
        $data = '';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $file = new File();
        $sessionId = 'todelete';
        $file->destroy($sessionId);
        static::assertTrue(true);
    }

    public function testGc()
    {
        $file = new File();
        $lifetime = -1000;
        $file->gc($lifetime);
        static::assertTrue(true);
    }

    public function testWriteClose()
    {
        $file = new File();
        $file->writeClose();
        static::assertTrue(true);
    }
}
