<?php
/**
 * Created by solly [01.11.17 14:06]
 */

namespace insolita\cqueue\Storage;

use function array_unshift;
use insolita\cqueue\Contracts\StorageInterface;

class PhpRedisStorage implements StorageInterface
{
    /**
     * @var \Redis
     */
    private $redis;
    
    public function __construct(\Redis $client)
    {
        $this->redis = $client;
    }
    
    public function delete($key)
    {
        return $this->redis->del($key);
    }
    
    public function listCount($key): int
    {
        return $this->redis->llen($key) ?? 0;
    }
    
    public function listItems($key): array
    {
        return $this->redis->lrange($key, 0, -1);
    }
    
    public function listPop($key)
    {
        return $this->redis->rpop($key);
    }
    
    public function listPush($key, array $values)
    {
        if (!empty($values)) {
            foreach ($values as $value) {
                $this->redis->lPush($key, $value);
            }
        }
    }
    
    public function zSetCount($key): int
    {
        return $this->redis->zcard($key) ?? 0;
    }
    
    public function zSetItems($key): array
    {
        return $this->redis->zrange($key, 0, -1);
    }
    
    public function zSetExpiredItems($key, $score): array
    {
        return $this->redis->zrevrangebyscore($key, $score, '-inf');
    }
    
    public function zSetPush($key, $score, $identity)
    {
        return $this->redis->zadd($key, $score, $identity);
    }
    
    public function zSetRem($key, $identity)
    {
        return $this->redis->zrem($key, $identity);
    }
    
    public function zSetExists($key, $identity): bool
    {
        $score = $this->redis->zScore($key, $identity);
        return (!is_null($score) && $score !== false);
    }
    
    public function moveFromZSetToList($listKey, $zSetKey, $item)
    {
        if ($this->redis->zrem($zSetKey, $item) == 1) {
            $this->redis->lpush($listKey, $item);
        }
        //If key not in set ?
    }
}
