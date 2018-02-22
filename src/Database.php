<?php

declare(strict_types=1);

namespace Rancoud\Session;

use Rancoud\Database\Configurator;
use Rancoud\Database\Database as Db;
use SessionHandlerInterface;

/**
 * Class Database.
 */
class Database implements SessionHandlerInterface
{
    /** @var \Rancoud\Database\Database */
    protected $db;
    protected $userId = null;

    /**
     * @param $configuration
     *
     * @throws \Exception
     */
    public function setNewDatabase($configuration)
    {
        if ($configuration instanceof Configurator) {
            $this->db = new Db($configuration);
        } else {
            $this->db = new Db(new Configurator($configuration));
        }
    }

    /**
     * @param $database
     */
    public function setCurrentDatabase($database)
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
     * @param $savePath
     * @param $sessionName
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
     * @param $sessionId
     *
     * @throws \Exception
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
     * @param $sessionId
     * @param $data
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $sql = 'REPLACE INTO sessions VALUES(:id, :id_user, NOW(), :content)';
        $params = ['id' => $sessionId, 'id_user' => $this->userId, 'content' => $data];

        return $this->db->exec($sql, $params);
    }

    /**
     * @param $sessionId
     *
     * @throws \Exception
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
     * @param $lifetime
     *
     * @throws \Exception
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
}
