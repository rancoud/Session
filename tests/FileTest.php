<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\File;
use Rancoud\Session\SessionException;

/**
 * Class FileTest.
 */
class FileTest extends TestCase
{
    protected function setUp(): void
    {
        $path = \ini_get('session.save_path');
        if (empty($path)) {
            $path = \DIRECTORY_SEPARATOR . 'tmp';
        }

        $pattern = $path . \DIRECTORY_SEPARATOR . 'sess_*';
        foreach (\glob($pattern) as $file) {
            if (\file_exists($file)) {
                \unlink($file);
            }
        }

        if (\is_dir($path . \DIRECTORY_SEPARATOR . 'tests')) {
            \rmdir($path . \DIRECTORY_SEPARATOR . 'tests');
        }
    }

    /**
     * @return string
     */
    private function getPath(): string
    {
        $path = \ini_get('session.save_path');
        if (empty($path)) {
            return \DIRECTORY_SEPARATOR . 'tmp';
        }

        return $path;
    }

    /**
     * @param File $file
     *
     * @throws SessionException
     */
    private function openSessionForSavingSavePath(File $file): void
    {
        $success = $file->open($this->getPath(), '');
        static::assertTrue($success);
    }

    /**
     * @throws SessionException
     */
    public function testOpen(): void
    {
        $file = new File();
        $savePath = $this->getPath();
        $sessionName = '';
        $success = $file->open($savePath, $sessionName);
        static::assertTrue($success);

        $savePathNotCreated = $savePath . \DIRECTORY_SEPARATOR . 'tests';
        $success = $file->open($savePathNotCreated, $sessionName);
        static::assertTrue($success);
        $success = \file_exists($savePathNotCreated);
        static::assertTrue($success);
    }

    public function testClose(): void
    {
        $file = new File();
        $success = $file->close();
        static::assertTrue($success);
    }

    /**
     * @throws SessionException
     */
    public function testWrite(): void
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataInFile = \file_get_contents($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertSame($data, $dataInFile);
    }

    /**
     * @throws SessionException
     */
    public function testRead(): void
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataOutput = $file->read($sessionId);
        static::assertNotEmpty($dataOutput);
        static::assertIsString($dataOutput);
        static::assertSame($data, $dataOutput);

        $sessionId = '';
        $dataOutput = $file->read($sessionId);
        static::assertEmpty($dataOutput);
        static::assertIsString($dataOutput);
    }

    /**
     * @throws SessionException
     */
    public function testDestroy(): void
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

        $isFileExist = \file_exists($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileExist);
        $success = $file->destroy($sessionId);
        static::assertTrue($success);
        $isFileNotExist = !\file_exists($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileNotExist);
    }

    /**
     * @throws SessionException
     */
    public function testGc(): void
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $isFileExist = \file_exists($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileExist);

        $lifetime = -1000;
        $success = $file->gc($lifetime);
        static::assertTrue($success);

        $isFileNotExist = !\file_exists($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertTrue($isFileNotExist);
    }

    /**
     * @throws SessionException
     */
    public function testValidateId(): void
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $baseId = 'XiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Xj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Xklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        $file->write($baseId . $endId1, 'a');

        static::assertTrue($file->validateId($baseId . $endId1));
        static::assertFalse($file->validateId($baseId . $endId2));
        static::assertFalse($file->validateId('kjlfez/fez'));
    }

    /**
     * @throws SessionException
     */
    public function testUpdateTimestamp(): void
    {
        $file = new File();

        $this->openSessionForSavingSavePath($file);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $file->write($sessionId, $data);
        static::assertTrue($success);

        $dataInFile = \file_get_contents($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        $oldFileModifiedTime = \filemtime($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertSame($data, $dataInFile);

        \sleep(1);
        $success = $file->updateTimestamp($sessionId, $data);
        static::assertTrue($success);

        \clearstatcache();

        $dataInFile2 = \file_get_contents($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);
        static::assertSame($data, $dataInFile2);
        static::assertSame($dataInFile, $dataInFile2);
        $newFileModifiedTime = \filemtime($this->getPath() . \DIRECTORY_SEPARATOR . 'sess_' . $sessionId);

        static::assertTrue($oldFileModifiedTime < $newFileModifiedTime);
    }

    /**
     * @throws \Exception
     */
    public function testCreateId(): void
    {
        $file = new File();

        $string = $file->create_sid();

        static::assertMatchesRegularExpression('/^[a-zA-Z0-9-]{127}+$/', $string);
    }

    /**
     * @throws SessionException
     */
    public function testLengthSessionID(): void
    {
        $file = new File();
        $file->setLengthSessionID(50);
        static::assertSame(50, $file->getLengthSessionID());
    }

    public function testLengthSessionIDSessionException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('could not set length session ID below 32');

        $file = new File();
        $file->setLengthSessionID(1);
    }
}
