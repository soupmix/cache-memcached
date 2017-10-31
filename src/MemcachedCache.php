<?php

namespace Soupmix\Cache;

use Soupmix\Cache\Exceptions\InvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Memcached;

class MemcachedCache implements CacheInterface
{

    const PSR16_RESERVED_CHARACTERS = ['{','}','(',')','/','@',':'];

    public $handler;

    /**
     * Connect to Memcached service
     *
     * @param Memcached $handler Memcached handler object
     *
     */
    public function __construct(Memcached $handler)
    {
        $this->handler = $handler;
        if (defined('Memcached::HAVE_IGBINARY') && extension_loaded('igbinary')) {
            ini_set('memcached.serializer', 'igbinary');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {

        $this->checkReservedCharacters($key);
        $value = $this->handler->get($key);
        return $value ?: $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value, $ttl = null)
    {

        $this->checkReservedCharacters($key);
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime('now'))->add($ttl)->getTimeStamp() - time();
        }
        return $this->handler->set($key, $value, (int) $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {

        $this->checkReservedCharacters($key);
        return (bool) $this->handler->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        return $this->handler->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getMultiple($keys, $default = null)
    {
        $defaults = array_fill(0, count($keys), $default);
        foreach ($keys as $key) {
            $this->checkReservedCharacters($key);
        }
        return array_merge($this->handler->getMulti($keys), $defaults);
    }

    /**
     * {@inheritDoc}
     */
    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->checkReservedCharacters($key);
        }
        if ($ttl instanceof DateInterval) {
            $ttl = (new DateTime('now'))->add($ttl)->getTimeStamp() - time();
        }
        return $this->handler->setMulti($values, (int) $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->checkReservedCharacters($key);
        }
        return $this->handler->deleteMulti($keys);
    }

    /**
     * {@inheritDoc}
     */
    public function increment($key, $step = 1)
    {
        return $this->handler->increment($key, $step);
    }

    /**
     * {@inheritDoc}
     */
    public function decrement($key, $step = 1)
    {
        return $this->handler->decrement($key, $step);
    }

    /**
     * {@inheritDoc}
     */
    public function has($key)
    {
        $this->checkReservedCharacters($key);
        $value = $this->handler->get($key);
        return Memcached::RES_NOTFOUND !== $this->handler->getResultCode();
    }

    private function checkReservedCharacters($key)
    {
        if (!is_string($key)) {
            $message = sprintf('key %s is not a string.', $key);
            throw new InvalidArgumentException($message);
        }
        foreach (self::PSR16_RESERVED_CHARACTERS as $needle) {
            if (strpos($key, $needle) !== false) {
                $message = sprintf('%s string is not a legal value.', $key);
                throw new InvalidArgumentException($message);
            }
        }
    }
}
