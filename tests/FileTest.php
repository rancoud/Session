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

        if (is_dir($path . DIRECTORY_SEPARATOR . 'tests')) {
            rmdir($path . DIRECTORY_SEPARATOR . 'tests');
        }
    }

    /**
     * @return string
     */
    private function getPath()
    {
        $path = ini_get('session.save_path');
        if (empty($path)) {
            return DIRECTORY_SEPARATOR . 'tmp';
        }

        return $path;
    }

    /**
     * @param File $file
     *
     * @throws \PHPUnit\Framework\AssertionFailedError
     */
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

        $savePathNotCreated = $savePath . DIRECTORY_SEPARATOR . 'tests';
        $success = $file->open($savePathNotCreated, $sessionName);
        static::assertTrue($success);
        $success = file_exists($savePathNotCreated);
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

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataInFile = file_get_contents($this->getPath() . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertEquals($data, $dataInFile);
    }

    public function testRead()
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataOutput = $file->read($sessionId);
        static::assertTrue(!empty($dataOutput));
        static::assertTrue(is_string($dataOutput));
        static::assertEquals($data, $dataOutput);

        $sessionId = '';
        $dataOutput = $file->read($sessionId);
        static::assertTrue(empty($dataOutput));
        static::assertTrue(is_string($dataOutput));
    }

    public function testDestroy()
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'todelete';
        $success = $file->destroy($sessionId);
        static::assertTrue($success);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $isFileExist = file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileExist);
        $success = $file->destroy($sessionId);
        static::assertTrue($success);
        $isFileNotExist = !file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileNotExist);
    }

    public function testGc()
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $isFileExist = file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileExist);

        $lifetime = -1000;
        $success = $file->gc($lifetime);
        static::assertTrue($success);

        $isFileNotExist = !file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileNotExist);
    }
}
