<?php

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Session\Database;

/**
 * Class DatabaseTest.
 */
class DatabaseTest extends TestCase
{
    private static $db;

    public static function setUpBeforeClass()
    {
        $conf = new \Rancoud\Database\Configurator([
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);
        static::$db = new \Rancoud\Database\Database($conf);
    }

    public function testOpen()
    {
        $database = new Database();
        $database->setCurrentDatabase(static::$db);

        $savePath = '';
        $sessionName = '';
        $success = $database->open($savePath, $sessionName);
        static::assertTrue($success);
    }

    public function testClose()
    {
        $database = new Database();
        $database->setCurrentDatabase(static::$db);

        $success = $database->close();
        static::assertTrue($success);
    }

    public function testWrite()
    {
        $database = new Database();
        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        try {
            $success = $database->write($sessionId, $data);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue($success);

        $sql = 'SELECT * FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $row = static::$db->selectRow($sql, $params);
        static::assertNotEmpty($row);
        static::assertEquals($data, $row['content']);
    }

    public function testRead()
    {
        $database = new Database();
        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        try {
            $database->write($sessionId, $data);
            $dataOutput = $database->read($sessionId);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue(!empty($dataOutput));
        static::assertTrue(is_string($dataOutput));
        static::assertEquals($data, $dataOutput);

        $sessionId = '';
        try {
            $dataOutput = $database->read($sessionId);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue(empty($dataOutput));
        static::assertTrue(is_string($dataOutput));
    }

    public function testDestroy()
    {
        $database = new Database();
        $database->setCurrentDatabase(static::$db);

        $sessionId = 'todelete';
        try {
            $success = $database->destroy($sessionId);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue($success);

        $sessionId = 'sessionId';
        $sql = 'SELECT COUNT(id) FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $isRowExist = (static::$db->count($sql, $params) === 1);

        static::assertTrue($isRowExist);
        try {
            $success = $database->destroy($sessionId);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue($success);
        $isRowNotExist = (static::$db->count($sql, $params) === 0);
        static::assertTrue($isRowNotExist);
    }

    public function testGc()
    {
        $database = new Database();
        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $sql = 'SELECT COUNT(id) FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];

        try {
            $success = $database->write($sessionId, $data);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue($success);

        $isRowExist = (static::$db->count($sql, $params) === 1);
        static::assertTrue($isRowExist);

        $lifetime = -1000;
        try {
            $success = $database->gc($lifetime);
        } catch (\Exception $e) {
            var_dump(static::$db->getErrors());
        }
        static::assertTrue($success);

        $isRowNotExist = (static::$db->count($sql, $params) === 0);
        static::assertTrue($isRowNotExist);
    }
}
