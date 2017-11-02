<?php
/**
 * Created by solly [01.11.17 14:03]
 */

namespace insolita\cqueue\Contracts;

interface StorageInterface
{
    public function delete($key);
    
    /**
     * @param string $key
     *
     * @return string|int
     */
    public function listPop($key);
    
    public function listPush($key, array $values);
    
    public function listCount($key): int;
    
    public function listItems($key): array;
    
    public function zSetItems($key): array;
    
    public function zSetCount($key): int;
    
    public function zSetExpiredItems($key, $score): array;
    
    public function zSetPush($key, $score, $identity);
    
    public function zSetRem($key, $identity);
    
    public function zSetExists($key, $identity): bool;
    
    public function moveFromZSetToList($listKey, $zSetKey, $value);
    
}
