<?php

/** @noinspection SqlDialectInspection */

declare(strict_types=1);

namespace Rancoud\Session;

use Rancoud\Database\Configurator;
use Rancoud\Database\Database as Db;
use Rancoud\Database\DatabaseException;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Class Database.
 */
class Database implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    /** @var Db */
    protected Db $db;

    /** @var int|null */
    protected ?int $userId = null;

    /**
     * @param Configurator|array $configuration
     *
     * @throws DatabaseException
     */
    public function setNewDatabase($configuration): void
    {
        if ($configuration instanceof Configurator) {
            $this->db = new Db($configuration);
        } else {
            $this->db = new Db(new Configurator($configuration));
        }
    }

    /**
     * @param Db $database
     */
    public function setCurrentDatabase($database): void
    {
        $this->db = $database;
    }

    /**
     * @param int $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @throws DatabaseException
     *
     * @return string
     */
    public function read($sessionId): string
    {
        $sql = 'SELECT content FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];

        return (string) $this->db->selectVar($sql, $params);
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @throws DatabaseException
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $sql = 'REPLACE INTO sessions VALUES(:id, :id_user, NOW(), :content)';
        $params = ['id' => $sessionId, 'id_user' => $this->userId, 'content' => $data];

        $this->db->exec($sql, $params);

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @throws DatabaseException
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        $sql = 'DELETE FROM sessions WHERE id = :id';
        $params = ['id' => $sessionId];
        $this->db->delete($sql, $params);

        return true;
    }

    /**
     * @param int $lifetime
     *
     * @throws DatabaseException
     *
     * @return bool
     */
    public function gc($lifetime): bool
    {
        $sql = 'DELETE FROM sessions WHERE DATE_ADD(last_access, INTERVAL :seconds second) < NOW()';
        $params = ['seconds' => $lifetime];
        $this->db->delete($sql, $params);

        return true;
    }

    /**
     * Checks format and id exists, if not session_id will be regenerate.
     *
     * @param string $key
     *
     * @throws DatabaseException
     *
     * @return bool
     */
    public function validateId($key): bool
    {
        if (\preg_match('/^[a-zA-Z0-9-]{127}+$/', $key) !== 1) {
            return false;
        }

        $sql = 'SELECT COUNT(id) FROM sessions WHERE id=:id';
        $params = ['id' => $key];
        $count = $this->db->count($sql, $params);

        return $count === 1;
    }

    /**
     * Updates the timestamp of a session when its data didn't change.
     *
     * @param string $sessionId
     * @param string $sessionData
     *
     * @throws DatabaseException
     *
     * @return bool
     */
    public function updateTimestamp($sessionId, $sessionData): bool
    {
        return $this->write($sessionId, $sessionData);
    }

    /**
     * @throws \Exception
     * @throws DatabaseException
     *
     * @return string
     */
    public function create_sid(): string
    {
        $string = '';
        $caracters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-';

        $countCaracters = 62;
        for ($i = 0; $i < 127; ++$i) {
            $string .= $caracters[\random_int(0, $countCaracters)];
        }

        $sql = 'SELECT COUNT(id) FROM sessions WHERE id=:id';
        $params = ['id' => $string];
        $count = $this->db->count($sql, $params);
        if ($count !== 0) {
            return $this->create_sid();
        }

        return $string;
    }
}
