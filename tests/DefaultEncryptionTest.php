<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\Session;

/**
 * Class DefaultEncryptionTest.
 */
class DefaultEncryptionTest extends TestCase
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
    }

    private function foundSessionFile()
    {
        $path = ini_get('session.save_path');
        if (empty($path)) {
            $path = DIRECTORY_SEPARATOR . 'tmp';
        }
        $id = session_id();

        $files = scandir($path);
        foreach ($files as $file) {
            if (mb_strpos($file, $id) !== false) {
                return file_get_contents($path . DIRECTORY_SEPARATOR . 'sess_' . $id);
            }
        }

        return false;
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadAndWrite()
    {
        Session::useDefaultEncryptionDriver('randomKey');

        Session::set('a', 'b');

        session_commit();
        $data = $this->foundSessionFile();
        static::assertNotFalse($data);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataDecrypted = $encryptionTrait->decrypt($data);
        static::assertEquals('a|s:1:"b";', $dataDecrypted);
    }
}
