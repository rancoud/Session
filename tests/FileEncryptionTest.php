<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\FileEncryption;

/**
 * Class FileEncryptionTest.
 */
class FileEncryptionTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        if (file_exists(__DIR__ . '/toto')) {
            rmdir(__DIR__ . '/toto');
        }
    }

    public static function setUpAfterClass()
    {
        if (file_exists(__DIR__ . '/toto')) {
            rmdir(__DIR__ . '/toto');
        }
    }

    public function testOpen()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $savePath = __DIR__;
        $sessionName = '';
        $success = $fileEncryption->open($savePath, $sessionName);
        static::assertTrue($success);

        $success = $fileEncryption->open($savePath . '/toto', $sessionName);
        static::assertTrue($success);
        $success = file_exists($savePath . '/toto');
        static::assertTrue($success);
    }

    public function testClose()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $success = $fileEncryption->close();
        static::assertTrue($success);
    }

    public function testWrite()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $sessionId = 'test';
        $data = 'azerty';
        $success = $fileEncryption->write($sessionId, $data);
        static::assertTrue($success);
    }

    public function testRead()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $sessionId = 'test';
        $data = $fileEncryption->read($sessionId);
        static::assertTrue(!empty($data));
        static::assertTrue(is_string($data));

        $sessionId = '';
        $data = $fileEncryption->read($sessionId);
        static::assertTrue(empty($data));
        static::assertTrue(is_string($data));
    }

    public function testDestroy()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $sessionId = 'todelete';
        $data = '';
        $success = $fileEncryption->write($sessionId, $data);
        static::assertTrue($success);

        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $sessionId = 'todelete';
        $fileEncryption->destroy($sessionId);
        static::assertTrue(true);
    }

    public function testGc()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');
        $lifetime = -1000;
        $fileEncryption->gc($lifetime);
        static::assertTrue(true);
    }
}
