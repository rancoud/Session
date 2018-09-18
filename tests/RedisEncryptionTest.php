<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Predis\Client as Predis;
use Rancoud\Session\RedisEncryption;

/**
 * Class RedisEncryptionTest.
 */
class RedisEncryptionTest extends TestCase
{
    /** @var \Predis\Client */
    private static $redis;

    public static function setUpBeforeClass()
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        static::$redis = new Predis($params);
        static::$redis->flushdb();
    }

    protected function setUp()
    {
        static::$redis->flushdb();
    }

    public function testOpen()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $savePath = '';
        $sessionName = '';
        $success = $redis->open($savePath, $sessionName);
        static::assertTrue($success);
    }

    public function testClose()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);
        $success = $redis->close();
        static::assertTrue($success);
    }

    public function testWrite()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis = static::$redis->get($sessionId);
        static::assertNotEquals($data, $dataInRedis);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInRedisDecrypted = $encryptionTrait->decrypt($dataInRedis);
        static::assertEquals($data, $dataInRedisDecrypted);
    }

    public function testRead()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataOutput = $redis->read($sessionId);
        static::assertTrue(!empty($dataOutput));
        static::assertTrue(is_string($dataOutput));
        static::assertEquals($data, $dataOutput);

        $sessionId = '';
        $dataOutput = $redis->read($sessionId);
        static::assertTrue(empty($dataOutput));
        static::assertTrue(is_string($dataOutput));
    }

    public function testDestroy()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'todelete';
        $success = $redis->destroy($sessionId);
        static::assertTrue($success);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $isKeyExist = static::$redis->exists($sessionId) === 1;
        static::assertTrue($isKeyExist);
        $success = $redis->destroy($sessionId);
        static::assertTrue($success);
        $isKeyNotExist = static::$redis->exists($sessionId) === 0;
        static::assertTrue($isKeyNotExist);
    }

    public function testGc()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);
        $redis->setLifetime(1);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $isKeyExist = static::$redis->exists($sessionId) === 1;
        static::assertTrue($isKeyExist);

        sleep(2);

        $lifetime = 0;
        $success = $redis->gc($lifetime);
        static::assertTrue($success);

        $isKeyNotExist = static::$redis->exists($sessionId) === 0;
        static::assertTrue($isKeyNotExist);
    }

    public function testSetNewRedis()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $params = [
            'scheme' => 'tcp',
            'host'   => '127.0.0.1',
            'port'   => 6379,
        ];
        $redis->setNewRedis($params);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);
    }

    public function testValidateId()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $baseId = 'DiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Dj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Dklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        $redis->write($baseId . $endId1, 'a');

        static::assertFalse($redis->validateId($baseId . $endId1));
        static::assertTrue($redis->validateId($baseId . $endId2));
        static::assertFalse($redis->validateId('kjlfez/fez'));
    }

    public function testUpdateTimestamp()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis = static::$redis->get($sessionId);
        static::assertNotEquals($data, $dataInRedis);
        $ttl1 = static::$redis->ttl($sessionId);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInRedisDecrypted = $encryptionTrait->decrypt($dataInRedis);
        static::assertEquals($data, $dataInRedisDecrypted);

        sleep(2);

        $ttl2 = static::$redis->ttl($sessionId);

        $success = $redis->updateTimestamp($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis2 = static::$redis->get($sessionId);
        static::assertNotEquals($data, $dataInRedis2);
        $ttl3 = static::$redis->ttl($sessionId);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInRedisDecrypted = $encryptionTrait->decrypt($dataInRedis2);
        static::assertEquals($data, $dataInRedisDecrypted);

        static::assertTrue($ttl2 < $ttl1);
        static::assertTrue($ttl3 > $ttl2);
    }

    public function testCreateId()
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $string = $redis->create_sid();

        static::assertTrue(preg_match('/^[a-zA-Z0-9-]{127}+$/', $string) === 1);
    }
}
