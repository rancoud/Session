<?php

/** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Predis\Client as Predis;
use Rancoud\Session\RedisEncryption;

/**
 * Class RedisEncryptionTest.
 */
class RedisEncryptionTest extends TestCase
{
    /** @var Predis */
    private static Predis $redis;

    public static function setUpBeforeClass(): void
    {
        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = \getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ? $redisHost : '127.0.0.1';

        static::$redis = new Predis($params);
        static::$redis->flushdb();
    }

    protected function setUp(): void
    {
        static::$redis->flushdb();
    }

    public function testOpen(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $savePath = '';
        $sessionName = '';
        $success = $redis->open($savePath, $sessionName);
        static::assertTrue($success);
    }

    public function testClose(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);
        $success = $redis->close();
        static::assertTrue($success);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testWrite(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis = static::$redis->get($sessionId);
        static::assertNotSame($data, $dataInRedis);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInRedisDecrypted = $encryptionTrait->decrypt($dataInRedis);
        static::assertSame($data, $dataInRedisDecrypted);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testRead(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataOutput = $redis->read($sessionId);
        static::assertNotEmpty($dataOutput);
        static::assertIsString($dataOutput);
        static::assertSame($data, $dataOutput);

        $sessionId = '';
        $dataOutput = $redis->read($sessionId);
        static::assertEmpty($dataOutput);
        static::assertIsString($dataOutput);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testDestroy(): void
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

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testGc(): void
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

        \sleep(2);

        $lifetime = 0;
        $success = $redis->gc($lifetime);
        static::assertTrue($success);

        $isKeyNotExist = static::$redis->exists($sessionId) === 0;
        static::assertTrue($isKeyNotExist);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testSetNewRedis(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = \getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ? $redisHost : '127.0.0.1';

        $redis->setNewRedis($params);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testValidateId(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $baseId = 'DiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Dj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Dklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        $redis->write($baseId . $endId1, 'a');

        static::assertTrue($redis->validateId($baseId . $endId1));
        static::assertFalse($redis->validateId($baseId . $endId2));
        static::assertFalse($redis->validateId('kjlfez/fez'));
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testUpdateTimestamp(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis = static::$redis->get($sessionId);
        static::assertNotSame($data, $dataInRedis);
        $ttl1 = static::$redis->ttl($sessionId);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInRedisDecrypted = $encryptionTrait->decrypt($dataInRedis);
        static::assertSame($data, $dataInRedisDecrypted);

        \sleep(2);

        $ttl2 = static::$redis->ttl($sessionId);

        $success = $redis->updateTimestamp($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis2 = static::$redis->get($sessionId);
        static::assertNotSame($data, $dataInRedis2);
        $ttl3 = static::$redis->ttl($sessionId);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInRedisDecrypted = $encryptionTrait->decrypt($dataInRedis2);
        static::assertSame($data, $dataInRedisDecrypted);

        static::assertTrue($ttl2 < $ttl1);
        static::assertTrue($ttl3 > $ttl2);
    }

    /**
     * @throws \Exception
     */
    public function testCreateId(): void
    {
        $redis = new RedisEncryption();
        $redis->setKey('randomKey');

        $redis->setCurrentRedis(static::$redis);

        $string = $redis->create_sid();

        static::assertSame(\preg_match('/^[a-zA-Z0-9-]{127}+$/', $string), 1);
    }
}
