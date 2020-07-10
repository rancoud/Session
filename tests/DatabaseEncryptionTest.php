<?php

/** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

namespace Rancoud\Session\Test;

use PHPUnit\Framework\TestCase;
use Rancoud\Database\Configurator;
use Rancoud\Database\DatabaseException;
use Rancoud\Session\DatabaseEncryption;

/**
 * Class DatabaseEncryptionTest.
 */
class DatabaseEncryptionTest extends TestCase
{
    /** @var \Rancoud\Database\Database */
    private static \Rancoud\Database\Database $db;

    /**
     * @throws DatabaseException
     */
    public static function setUpBeforeClass(): void
    {
        $conf = new Configurator([
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);
        static::$db = new \Rancoud\Database\Database($conf);

        $sql = '
            CREATE TABLE IF NOT EXISTS `sessions` (
              `id` varchar(128) NOT NULL,
              `id_user` int(10) unsigned DEFAULT NULL,
              `last_access` datetime NOT NULL,
              `content` text NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ';
        try {
            static::$db->exec($sql);
            static::$db->truncateTables('sessions');
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
    }

    protected function setUp(): void
    {
        try {
            static::$db->truncateTables('sessions');
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
    }

    public function testOpen(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $savePath = '';
        $sessionName = '';
        $success = $database->open($savePath, $sessionName);
        static::assertTrue($success);
    }

    public function testClose(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $success = $database->close();
        static::assertTrue($success);
    }

    /**
     * @throws DatabaseException
     * @throws \Rancoud\Session\SessionException
     */
    public function testWrite(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $sql = 'SELECT * FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $row = static::$db->selectRow($sql, $params);
        static::assertNotEmpty($row);
        static::assertNotEquals($data, $row['content']);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInDatabaseDecrypted = $encryptionTrait->decrypt($row['content']);
        static::assertEquals($data, $dataInDatabaseDecrypted);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testRead(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        try {
            $database->write($sessionId, $data);
            $dataOutput = $database->read($sessionId);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertNotEmpty($dataOutput);
        static::assertIsString($dataOutput);
        static::assertEquals($data, $dataOutput);

        $sessionId = '';
        try {
            $dataOutput = $database->read($sessionId);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertEmpty($dataOutput);
        static::assertIsString($dataOutput);
    }

    /**
     * @throws DatabaseException
     * @throws \Rancoud\Session\SessionException
     */
    public function testDestroy(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'todelete';
        try {
            $success = $database->destroy($sessionId);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $sessionId = 'sessionId';
        $data = 'azerty';
        try {
            $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }

        $sql = 'SELECT COUNT(id) FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $isRowExist = (static::$db->count($sql, $params) === 1);

        static::assertTrue($isRowExist);
        try {
            $success = $database->destroy($sessionId);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);
        $isRowNotExist = (static::$db->count($sql, $params) === 0);
        static::assertTrue($isRowNotExist);
    }

    /**
     * @throws DatabaseException
     * @throws \Rancoud\Session\SessionException
     */
    public function testGc(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $sql = 'SELECT COUNT(id) FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];

        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $isRowExist = (static::$db->count($sql, $params) === 1);
        static::assertTrue($isRowExist);

        $lifetime = -1000;
        try {
            $success = $database->gc($lifetime);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $isRowNotExist = (static::$db->count($sql, $params) === 0);
        static::assertTrue($isRowNotExist);
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testSetUserId(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';
        $userId = 5;
        $database->setUserId($userId);

        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $sql = 'SELECT id_user FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        try {
            $userIdInDatabase = static::$db->selectVar($sql, $params);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertNotNull($userIdInDatabase);
        static::assertEquals($userId, $userIdInDatabase);

        $userId = null;
        $database->setUserId($userId);

        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $sql = 'SELECT id_user FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        try {
            $userIdInDatabase = static::$db->selectVar($sql, $params);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertNull($userIdInDatabase);
        static::assertEquals($userId, $userIdInDatabase);
    }

    /**
     * @throws DatabaseException
     * @throws \Rancoud\Session\SessionException
     */
    public function testSetNewDatabaseWithArray(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $params = [
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];
        $database->setNewDatabase($params);

        $sessionId = 'sessionId';
        $data = 'azerty';

        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);
    }

    /**
     * @throws DatabaseException
     * @throws \Rancoud\Session\SessionException
     */
    public function testSetNewDatabaseWithConfigurator(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $params = [
            'engine'   => 'mysql',
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];
        $conf = new Configurator($params);
        $database->setNewDatabase($conf);

        $sessionId = 'sessionId';
        $data = 'azerty';

        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);
    }

    /**
     * @throws DatabaseException
     * @throws \Rancoud\Session\SessionException
     */
    public function testValidateId(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setCurrentDatabase(static::$db);

        $baseId = 'DiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Dj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Dklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        try {
            $database->write($baseId . $endId1, 'a');
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }

        static::assertTrue($database->validateId($baseId . $endId1));
        static::assertFalse($database->validateId($baseId . $endId2));
        static::assertFalse($database->validateId('kjlfez/fez'));
    }

    /**
     * @throws \Rancoud\Session\SessionException
     */
    public function testUpdateTimestamp(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $success = false;
        try {
            $success = $database->write($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }
        static::assertTrue($success);

        $sql = 'SELECT * FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];

        try {
            $row1 = static::$db->selectRow($sql, $params);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }

        static::assertNotEmpty($row1);
        static::assertNotEquals($data, $row1['content']);

        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInDatabaseDecrypted = $encryptionTrait->decrypt($row1['content']);
        static::assertEquals($data, $dataInDatabaseDecrypted);

        sleep(1);

        try {
            $success = $database->updateTimestamp($sessionId, $data);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }

        static::assertTrue($success);

        $sql = 'SELECT * FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];

        try {
            $row2 = static::$db->selectRow($sql, $params);
        } catch (DatabaseException $e) {
            var_dump(static::$db->getErrors());

            return;
        }

        static::assertNotEmpty($row2);
        static::assertNotEquals($data, $row2['content']);
        $encryptionTrait = $this->getObjectForTrait('Rancoud\Session\Encryption');
        $encryptionTrait->setKey('randomKey');
        $dataInDatabaseDecrypted = $encryptionTrait->decrypt($row2['content']);
        static::assertEquals($data, $dataInDatabaseDecrypted);

        static::assertTrue($row1['last_access'] < $row2['last_access']);
    }

    /**
     * @throws DatabaseException
     */
    public function testCreateId(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setCurrentDatabase(static::$db);

        $string = $database->create_sid();

        static::assertSame(preg_match('/^[a-zA-Z0-9-]{127}+$/', $string), 1);
    }
}
