<?php

/** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Predis\Client as Predis;
use Rancoud\Session\Redis;

/**
 * Class RedisTest.
 */
class RedisTest extends TestCase
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

        $redisHost = getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ?  $redisHost : '127.0.0.1';

        static::$redis = new Predis($params);
        static::$redis->flushdb();
    }

    protected function setUp(): void
    {
        static::$redis->flushdb();
    }

    public function testOpen(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);

        $savePath = '';
        $sessionName = '';
        $success = $redis->open($savePath, $sessionName);
        static::assertTrue($success);
    }

    public function testClose(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);
        $success = $redis->close();
        static::assertTrue($success);
    }

    public function testWrite(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis = static::$redis->get($sessionId);
        static::assertEquals($data, $dataInRedis);
    }

    public function testRead(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataOutput = $redis->read($sessionId);
        static::assertNotEmpty($dataOutput);
        static::assertIsString($dataOutput);
        static::assertEquals($data, $dataOutput);

        $sessionId = '';
        $dataOutput = $redis->read($sessionId);
        static::assertEmpty($dataOutput);
        static::assertIsString($dataOutput);
    }

    public function testDestroy(): void
    {
        $redis = new Redis();
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

    public function testGc(): void
    {
        $redis = new Redis();
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

    public function testSetNewRedis(): void
    {
        $redis = new Redis();
        $params = [
            'scheme' => 'tcp',
            'host'   => 'redis',
            'port'   => 6379,
        ];

        $redisHost = getenv('REDIS_HOST', true);
        $params['host'] = ($redisHost !== false) ?  $redisHost : '127.0.0.1';

        $redis->setNewRedis($params);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);
    }

    public function testValidateId(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);

        $baseId = 'DiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Dj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Dklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        $redis->write($baseId . $endId1, 'a');

        static::assertTrue($redis->validateId($baseId . $endId1));
        static::assertFalse($redis->validateId($baseId . $endId2));
        static::assertFalse($redis->validateId('kjlfez/fez'));
    }

    public function testUpdateTimestamp(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = $redis->write($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis = static::$redis->get($sessionId);
        static::assertEquals($data, $dataInRedis);
        $ttl1 = static::$redis->ttl($sessionId);

        sleep(2);

        $ttl2 = static::$redis->ttl($sessionId);

        $success = $redis->updateTimestamp($sessionId, $data);
        static::assertTrue($success);

        $dataInRedis2 = static::$redis->get($sessionId);
        static::assertEquals($data, $dataInRedis2);
        $ttl3 = static::$redis->ttl($sessionId);

        static::assertTrue($ttl2 < $ttl1);
        static::assertTrue($ttl3 > $ttl2);
    }

    /**
     * @throws \Exception
     */
    public function testCreateId(): void
    {
        $redis = new Redis();
        $redis->setCurrentRedis(static::$redis);

        $string = $redis->create_sid();

        static::assertSame(preg_match('/^[a-zA-Z0-9-]{127}+$/', $string), 1);
    }
}
