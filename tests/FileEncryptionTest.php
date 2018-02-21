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
    private function getPath()
    {
        $path = ini_get('session.save_path');
        if (empty($path)) {
            return DIRECTORY_SEPARATOR . 'tmp';
        }

        return $path;
    }

    private function openSessionForSavingSavePath(FileEncryption $fileEncryption)
    {
        $success = $fileEncryption->open($this->getPath(), '');
        static::assertTrue($success);
    }

    public function testOpen()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');

        $savePath = $this->getPath();
        $sessionName = '';
        $success = $fileEncryption->open($savePath, $sessionName);
        static::assertTrue($success);

        $success = $fileEncryption->open($savePath . '/tests', $sessionName);
        static::assertTrue($success);
        $success = file_exists($savePath . '/tests');
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

        $this->openSessionForSavingSavePath($fileEncryption);

        $sessionId = 'test';
        $data = 'azerty';
        $success = $fileEncryption->write($sessionId, $data);
        static::assertTrue($success);
    }

    public function testRead()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');

        $this->openSessionForSavingSavePath($fileEncryption);

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

        $this->openSessionForSavingSavePath($fileEncryption);

        $sessionId = 'todelete';
        $data = '';
        $success = $fileEncryption->write($sessionId, $data);
        static::assertTrue($success);

        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');

        $this->openSessionForSavingSavePath($fileEncryption);

        $sessionId = 'todelete';
        $fileEncryption->destroy($sessionId);
        static::assertTrue(true);
    }

    public function testGc()
    {
        $fileEncryption = new FileEncryption();
        $fileEncryption->setKey('randomKey');

        $this->openSessionForSavingSavePath($fileEncryption);

        $lifetime = -1000;
        $fileEncryption->gc($lifetime);
        static::assertTrue(true);
    }
}
