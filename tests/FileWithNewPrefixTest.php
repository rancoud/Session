<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\File;

/**
 * Class FileWithNewPrefixTest.
 */
class FileWithNewPrefixTest extends TestCase
{
    protected function setUp()
    {
        $path = ini_get('session.save_path');
        if (empty($path)) {
            $path = DIRECTORY_SEPARATOR . 'tmp';
        }

        $pattern = $path . DIRECTORY_SEPARATOR . 'myprefix_*';
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
        $file->setPrefix('myprefix_');
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
        $file->setPrefix('myprefix_');
        $success = $file->close();
        static::assertTrue($success);
    }

    public function testWrite()
    {
        $file = new File();
        $file->setPrefix('myprefix_');

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataInFile = file_get_contents($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertEquals($data, $dataInFile);
    }

    public function testRead()
    {
        $file = new File();
        $file->setPrefix('myprefix_');

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
        $file->setPrefix('myprefix_');

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'todelete';
        $success = $file->destroy($sessionId);
        static::assertTrue($success);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $isFileExist = file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertTrue($isFileExist);
        $success = $file->destroy($sessionId);
        static::assertTrue($success);
        $isFileNotExist = !file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertTrue($isFileNotExist);
    }

    public function testGc()
    {
        $file = new File();
        $file->setPrefix('myprefix_');

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $isFileExist = file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertTrue($isFileExist);

        $lifetime = -1000;
        $success = $file->gc($lifetime);
        static::assertTrue($success);

        $isFileNotExist = !file_exists($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertTrue($isFileNotExist);
    }

    public function testValidateId()
    {
        $file = new File();
        $file->setPrefix('myprefix_');

        $this->openSessionForSavingSavePath($file);

        $baseId = 'YiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Yj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Yklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        $file->write($baseId . $endId1, 'a');

        static::assertFalse($file->validateId($baseId . $endId1));
        static::assertTrue($file->validateId($baseId . $endId2));
        static::assertFalse($file->validateId('kjlfez/fez'));
    }

    public function testUpdateTimestamp()
    {
        $file = new File();
        $file->setPrefix('myprefix_');

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataInFile = file_get_contents($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        $oldFileModifiedTime = filemtime($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertEquals($data, $dataInFile);

        sleep(1);
        $success = $file->updateTimestamp($sessionId, $data);
        static::assertTrue($success);

        clearstatcache();

        $dataInFile2 = file_get_contents($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);
        static::assertEquals($data, $dataInFile2);
        static::assertEquals($dataInFile, $dataInFile2);
        $newFileModifiedTime = filemtime($this->getPath() . DIRECTORY_SEPARATOR . 'myprefix_' . $sessionId);

        static::assertTrue($oldFileModifiedTime < $newFileModifiedTime);
    }

    public function testCreateId()
    {
        $file = new File();
        $file->setPrefix('myprefix_');

        $string = $file->create_sid();

        static::assertTrue(preg_match('/^[a-zA-Z0-9-]{127}+$/', $string) === 1);
    }
}
