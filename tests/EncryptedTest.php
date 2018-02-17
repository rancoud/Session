<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\Encrypted;

/**
 * Class EncryptedTest.
 */
class EncryptedTest extends TestCase
{
    public function testMyBehavior() {
        $behavior = $this->getObjectForTrait('Rancoud\Session\Encrypted');

        $dataToEncrypt = 'this is something to encrypt';

        $behavior->setKey("my key");
        $encryptedData = $behavior->encrypt($dataToEncrypt);

        $finalData = $behavior->decrypt($encryptedData);
        $this->assertEquals($dataToEncrypt, $finalData);
    }
}
