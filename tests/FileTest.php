<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\File;

/**
 * Class FileTest.
 */
class FileTest extends TestCase
{
    private function getPath()
    {
        $path = ini_get('session.save_path');
        if (empty($path)) {
            return DIRECTORY_SEPARATOR . 'tmp';
        }

        return $path;
    }

    private function openSessionForSavingSavePath(File $file)
    {
        $success = $file->open($this->getPath(), '');
        static::assertTrue($success);
    }

    public function testOpen()
    {
        $file = new File();
        $savePath = $this->getPath();
        $sessionName = '';
        $success = $file->open($savePath, $sessionName);
        static::assertTrue($success);

        $success = $file->open($savePath . '/tests', $sessionName);
        static::assertTrue($success);
        $success = file_exists($savePath . '/tests');
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

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'test';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);
    }

    public function testRead()
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

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

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'todelete';
        $data = '';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $file = new File();
        $this->openSessionForSavingSavePath($file);

        $sessionId = 'todelete';
        $file->destroy($sessionId);
        static::assertTrue(true);
    }

    public function testGc()
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $lifetime = -1000;
        $file->gc($lifetime);
        static::assertTrue(true);
    }
}
