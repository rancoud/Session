<?php

/** @noinspection SqlResolve */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use Rancoud\Database\Configurator;
use Rancoud\Database\Database as DB;
use Rancoud\Database\DatabaseException;
use Rancoud\Session\DatabaseEncryption;
use Rancoud\Session\SessionException;

/**
 * Class DatabaseEncryptionTest.
 *
 * @internal
 */
class DatabaseEncryptionTest extends TestCase
{
    protected static DB $db;

    /** @throws DatabaseException */
    public static function setUpBeforeClass(): void
    {
        $conf = new Configurator([
            'driver'   => 'mysql',
            'host'     => 'mariadb',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);

        $mysqlHost = \getenv('MYSQL_HOST', true);
        $conf->setHost(($mysqlHost !== false) ? $mysqlHost : '127.0.0.1');

        static::$db = new DB($conf);

        $sql = <<<'SQL'
            CREATE TABLE IF NOT EXISTS `sessions` (
              `id` varchar(128) NOT NULL,
              `id_user` int(10) unsigned DEFAULT NULL,
              `last_access` datetime NOT NULL,
              `content` text NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        SQL;

        static::$db->exec($sql);
        static::$db->truncateTables('sessions');
    }

    /** @throws DatabaseException */
    protected function setUp(): void
    {
        static::$db->truncateTables('sessions');
    }

    public function testOpen(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $savePath = '';
        $sessionName = '';

        static::assertTrue($database->open($savePath, $sessionName));
    }

    public function testClose(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        static::assertTrue($database->close());
    }

    /**
     * @throws DatabaseException
     * @throws SessionException
     */
    public function testWrite(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';

        static::assertTrue($database->write($sessionId, $data));

        $sql = 'SELECT * FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $row = static::$db->selectRow($sql, $params);
        static::assertNotEmpty($row);
        static::assertNotSame($data, $row['content']);

        $encryptionTrait = new class() {
            use \Rancoud\Session\Encryption;
        };
        $encryptionTrait->setKey('randomKey');
        $dataInDatabaseDecrypted = $encryptionTrait->decrypt($row['content']);
        static::assertSame($data, $dataInDatabaseDecrypted);
    }

    /** @throws SessionException */
    public function testRead(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';

        $database->write($sessionId, $data);
        $dataOutput = $database->read($sessionId);

        static::assertNotEmpty($dataOutput);
        static::assertIsString($dataOutput);
        static::assertSame($data, $dataOutput);

        $sessionId = '';
        $dataOutput = $database->read($sessionId);

        static::assertEmpty($dataOutput);
        static::assertIsString($dataOutput);
    }

    /**
     * @throws DatabaseException
     * @throws SessionException
     */
    public function testDestroy(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $database->setCurrentDatabase(static::$db);

        $sessionId = 'todelete';
        static::assertTrue($database->destroy($sessionId));

        $sessionId = 'sessionId';
        $data = 'azerty';
        $database->write($sessionId, $data);

        $sql = 'SELECT COUNT(id) FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $isRowExist = (static::$db->count($sql, $params) === 1);

        static::assertTrue($isRowExist);
        static::assertTrue($database->destroy($sessionId));
        $isRowNotExist = (static::$db->count($sql, $params) === 0);
        static::assertTrue($isRowNotExist);
    }

    /**
     * @throws DatabaseException
     * @throws SessionException
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

        static::assertTrue($database->write($sessionId, $data));

        $isRowExist = (static::$db->count($sql, $params) === 1);
        static::assertTrue($isRowExist);

        $lifetime = -1000;
        static::assertTrue($database->gc($lifetime));

        $isRowNotExist = (static::$db->count($sql, $params) === 0);
        static::assertTrue($isRowNotExist);
    }

    /**
     * @throws DatabaseException
     * @throws SessionException
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

        static::assertTrue($database->write($sessionId, $data));

        $sql = 'SELECT id_user FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $userIdInDatabase = (int) static::$db->selectVar($sql, $params);
        static::assertNotNull($userIdInDatabase);
        static::assertSame($userId, $userIdInDatabase);

        $userId = null;
        $database->setUserId($userId);

        static::assertTrue($database->write($sessionId, $data));

        $userIdInDatabase = static::$db->selectVar($sql, $params);
        static::assertNull($userIdInDatabase);
        static::assertSame($userId, $userIdInDatabase);
    }

    /** @throws SessionException */
    public function testSetNewDatabaseWithArray(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $params = [
            'driver'   => 'mysql',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ];

        $mysqlHost = \getenv('MYSQL_HOST', true);
        $params['host'] = ($mysqlHost !== false) ? $mysqlHost : '127.0.0.1';

        $database->setNewDatabase($params);

        $sessionId = 'sessionId';
        $data = 'azerty';

        static::assertTrue($database->write($sessionId, $data));
    }

    /**
     * @throws DatabaseException
     * @throws SessionException
     */
    public function testSetNewDatabaseWithConfigurator(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');

        $conf = new Configurator([
            'driver'   => 'mysql',
            'host'     => 'mariadb',
            'user'     => 'root',
            'password' => '',
            'database' => 'test_database'
        ]);

        $mysqlHost = \getenv('MYSQL_HOST', true);
        $conf->setHost(($mysqlHost !== false) ? $mysqlHost : '127.0.0.1');

        $database->setNewDatabase($conf);

        $sessionId = 'sessionId';
        $data = 'azerty';

        static::assertTrue($database->write($sessionId, $data));
    }

    /** @throws SessionException */
    public function testSetNewDatabaseSessionException(): void
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionMessage('could not set database: "invalid" settings is not recognized');

        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setNewDatabase([
            'invalid'  => 'invalid'
        ]);
    }

    /** @throws SessionException */
    public function testValidateId(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setCurrentDatabase(static::$db);

        $baseId = 'DiqKrZDUGp5ubt3klF0oorIlFiADXC9jxig9e8leUcCYuZ9w0mXh0b1foEGIBs7SSsdOuLor58vU5liBRVPsTobnvt';
        $endId1 = 'Dj8hh65DlR3tTFI1SGX3mFciDA9rMOa4LlnMr';
        $endId2 = 'Dklezfoipvfk0lferijkoefzjklgrvefLlnMr';

        $database->write($baseId . $endId1, 'a');

        static::assertTrue($database->validateId($baseId . $endId1));
        static::assertFalse($database->validateId($baseId . $endId2));
        static::assertFalse($database->validateId('kjlfez/fez'));
    }

    /**
     * @throws DatabaseException
     * @throws SessionException
     */
    public function testUpdateTimestamp(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setCurrentDatabase(static::$db);

        $sessionId = 'sessionId';
        $data = 'azerty';

        static::assertTrue($database->write($sessionId, $data));

        $sql = 'SELECT * FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];

        $row1 = static::$db->selectRow($sql, $params);
        static::assertNotEmpty($row1);
        static::assertNotSame($data, $row1['content']);

        $encryptionTrait = new class() {
            use \Rancoud\Session\Encryption;
        };
        $encryptionTrait->setKey('randomKey');
        $dataInDatabaseDecrypted = $encryptionTrait->decrypt($row1['content']);
        static::assertSame($data, $dataInDatabaseDecrypted);

        \sleep(1);

        static::assertTrue($database->updateTimestamp($sessionId, $data));

        $row2 = static::$db->selectRow($sql, $params);

        static::assertNotEmpty($row2);
        static::assertNotSame($data, $row2['content']);
        $encryptionTrait = new class() {
            use \Rancoud\Session\Encryption;
        };
        $encryptionTrait->setKey('randomKey');
        $dataInDatabaseDecrypted = $encryptionTrait->decrypt($row2['content']);
        static::assertSame($data, $dataInDatabaseDecrypted);

        static::assertTrue($row1['last_access'] < $row2['last_access']);
    }

    /** @throws SessionException */
    public function testCreateId(): void
    {
        $database = new DatabaseEncryption();
        $database->setKey('randomKey');
        $database->setCurrentDatabase(static::$db);

        $string = $database->create_sid();

        static::assertMatchesRegularExpression('/^[a-zA-Z0-9-]{127}+$/', $string);
    }
}
