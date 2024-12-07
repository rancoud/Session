<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\Session;

/**
 * Class DefaultEncryptionTest.
 */
class DefaultEncryptionTest extends TestCase
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
    }

    private function foundSessionFile()
    {
        $path = \ini_get('session.save_path');
        if (empty($path)) {
            $path = \DIRECTORY_SEPARATOR . 'tmp';
        }
        $id = \session_id();

        $files = \scandir($path);
        foreach ($files as $file) {
            if (\mb_strpos($file, $id) !== false) {
                return \file_get_contents($path . \DIRECTORY_SEPARATOR . 'sess_' . $id);
            }
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function testReadAndWrite(): void
    {
        Session::useDefaultEncryptionDriver('randomKey');

        Session::set('a', 'b');

        \session_write_close();
        $data = $this->foundSessionFile();
        static::assertNotFalse($data);

        $encryptionTrait = new class {
            use \Rancoud\Session\Encryption;
        };
        $encryptionTrait->setKey('randomKey');
        $dataDecrypted = $encryptionTrait->decrypt($data);
        static::assertSame('a|s:1:"b";', $dataDecrypted);
    }
}
