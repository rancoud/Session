<?php

declare(strict_types=1);

namespace Rancoud\Session;

/**
 * Class File.
 */
class File implements \SessionHandlerInterface, \SessionUpdateTimestampHandlerInterface
{
    protected ?string $savePath = null;

    protected string $prefix = 'sess_';

    protected int $lengthSessionID = 127;

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /** @throws SessionException */
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
     * @throws SessionException
     */
    public function open($path, $name): bool
    {
        $this->savePath = $path;

        if (!\is_dir($this->savePath) && !\mkdir($this->savePath, 0o750) && !\is_dir($this->savePath)) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            throw new SessionException(\sprintf('Directory "%s" was not created', $this->savePath));
            // @codeCoverageIgnoreEnd
        }

        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /** @param string $id */
    public function read($id): string
    {
        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $id;
        if (\file_exists($filename) && \is_file($filename)) {
            return (string) \file_get_contents($filename);
        }

        return '';
    }

    /**
     * @param string $id
     * @param string $data
     */
    public function write($id, $data): bool
    {
        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $id;

        return !(\file_put_contents($filename, $data) === false);
    }

    /** @param string $id */
    public function destroy($id): bool
    {
        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $id;
        if (\file_exists($filename) && \is_file($filename)) {
            \unlink($filename);
        }

        return true;
    }

    /**
     * @param int $max_lifetime
     *
     * @noinspection PhpLanguageLevelInspection
     */
    #[\ReturnTypeWillChange]
    public function gc($max_lifetime): bool
    {
        $pattern = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . '*';
        foreach (\glob($pattern) as $file) {
            if (\file_exists($file) && \is_file($file) && \filemtime($file) + $max_lifetime < \time()) {
                \unlink($file);
            }
        }

        return true;
    }

    /**
     * Checks format and id exists, if not session_id will be regenerate.
     *
     * @param string $id
     *
     * @noinspection PhpMissingParamTypeInspection
     */
    public function validateId($id): bool
    {
        if (\preg_match('/^[a-zA-Z0-9-]{' . $this->lengthSessionID . '}+$/', $id) !== 1) {
            return false;
        }

        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $id;

        return \file_exists($filename);
    }

    /**
     * Updates the timestamp of a session when its data didn't change.
     *
     * @param string $id
     * @param string $data
     *
     * @noinspection PhpMissingParamTypeInspection
     */
    public function updateTimestamp($id, $data): bool
    {
        return $this->write($id, $data);
    }

    /**
     * @throws SessionException
     *
     * @noinspection PhpMethodNamingConventionInspection
     */
    public function create_sid(): string // phpcs:ignore
    {
        $string = '';
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-';

        try {
            $countCharacters = 62;
            for ($i = 0; $i < $this->lengthSessionID; ++$i) {
                $string .= $characters[\random_int(0, $countCharacters)];
            }
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            /* If an appropriate source of randomness cannot be found, an Exception will be thrown.
             * The list of randomness: https://www.php.net/manual/en/function.random-int.php
             */
            throw new SessionException('could not create sid: ' . $e->getMessage(), $e->getCode(), $e->getPrevious());
            // @codeCoverageIgnoreEnd
        }

        $filename = $this->savePath . \DIRECTORY_SEPARATOR . $this->prefix . $string;
        if (\file_exists($filename)) {
            // @codeCoverageIgnoreStart
            // Could not reach this statement without mocking the filesystem
            return $this->create_sid();
            // @codeCoverageIgnoreEnd
        }

        return $string;
    }
}
