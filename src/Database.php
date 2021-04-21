<?php

/** @noinspection SqlDialectInspection */

declare(strict_types=1);

namespace Rancoud\Session;

use Rancoud\Database\Configurator;
use Rancoud\Database\Database as DB;
use Rancoud\Database\DatabaseException;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

/**
 * Class Database.
 */
class Database implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    protected DB $db;

    protected ?int $userId = null;

    protected int $lengthSessionID = 127;

    /**
     * @param Configurator|array $configuration
     *
     * @throws SessionException
     */
    public function setNewDatabase($configuration): void
    {
        try {
            if ($configuration instanceof Configurator) {
                $this->db = new DB($configuration);
            } else {
                $this->db = new DB(new Configurator($configuration));
            }
        } catch (DatabaseException $e) {
            throw new SessionException('could not set database: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param DB $database
     */
    public function setCurrentDatabase(DB $database): void
    {
        $this->db = $database;
    }

    /**
     * @param int|null $userId
     */
    public function setUserId(?int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @param int $length
     *
     * @throws SessionException
     */
    public function setLengthSessionID(int $length): void
    {
        if ($length < 32) {
            throw new SessionException('could not set length session ID below 32');
        }

        $this->lengthSessionID = $length;
    }

    public function getLengthSessionID(): int
    {
        return $this->lengthSessionID;
    }

    /**
     * @param string $path
     * @param string $name
     *
     * @return bool
     */
    public function open($path, $name): bool
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
     * @param string $id
     *
     * @throws SessionException
     *
     * @return string
     */
    public function read($id): string
    {
        try {
            $sql = 'SELECT content FROM sessions WHERE id = :id';
            $params = ['id' => $id];

            return (string) $this->db->selectVar($sql, $params);
        } catch (DatabaseException $e) {
            throw new SessionException('could not read session: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param string $id
     * @param string $data
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function write($id, $data): bool
    {
        try {
            $sql = 'REPLACE INTO sessions VALUES(:id, :id_user, UTC_TIMESTAMP(), :content)';
            $params = ['id' => $id, 'id_user' => $this->userId, 'content' => $data];

            $this->db->exec($sql, $params);

            return true;
        } catch (DatabaseException $e) {
            throw new SessionException('could not update session: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param string $id
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function destroy($id): bool
    {
        try {
            $sql = 'DELETE FROM sessions WHERE id = :id';
            $params = ['id' => $id];
            $this->db->delete($sql, $params);

            return true;
        } catch (DatabaseException $e) {
            throw new SessionException('could not delete session: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param int $max_lifetime
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function gc($max_lifetime): bool
    {
        try {
            $sql = 'DELETE FROM sessions WHERE DATE_ADD(last_access, INTERVAL :seconds second) < UTC_TIMESTAMP()';
            $params = ['seconds' => $max_lifetime];
            $this->db->delete($sql, $params);

            return true;
        } catch (DatabaseException $e) {
            throw new SessionException('could not clean old sessions: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Checks format and id exists, if not session_id will be regenerate.
     *
     * @param string $id
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function validateId($id): bool
    {
        try {
            if (\preg_match('/^[a-zA-Z0-9-]{' . $this->lengthSessionID . '}+$/', $id) !== 1) {
                return false;
            }

            $sql = 'SELECT COUNT(id) FROM sessions WHERE id=:id';
            $params = ['id' => $id];
            $count = $this->db->count($sql, $params);

            return $count === 1;
        } catch (DatabaseException $e) {
            throw new SessionException('could not validate id: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Updates the timestamp of a session when its data didn't change.
     *
     * @param string $id
     * @param string $data
     *
     * @throws SessionException
     *
     * @return bool
     */
    public function updateTimestamp($id, $data): bool
    {
        return $this->write($id, $data);
    }

    /**
     * @throws SessionException
     *
     * @return string
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function create_sid(): string
    {
        try {
            $string = '';
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-';

            $countCharacters = 62;
            for ($i = 0; $i < $this->lengthSessionID; ++$i) {
                $string .= $characters[\random_int(0, $countCharacters)];
            }

            $sql = 'SELECT COUNT(id) FROM sessions WHERE id=:id';
            $params = ['id' => $string];
            $count = $this->db->count($sql, $params);
            if ($count !== 0) {
                // @codeCoverageIgnoreStart
                /* Could not reach this statement without mocking the function
                 */
                return $this->create_sid();
                // @codeCoverageIgnoreEnd
            }

            return $string;
        } catch (\Exception $e) {
            throw new SessionException('could not create sid: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }
}
