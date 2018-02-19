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

    /**
     * @param $configuration
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
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        return file_put_contents($this->savePath . '/sess_' . $sessionId, $data) === false ? false : true;
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
     * @return bool
     */
    public function gc($lifetime): bool
    {
        $sql = 'DELETE FROM sessions WHERE expire_at < NOW()';
        $this->db->delete($sql);

        return true;
    }
}
