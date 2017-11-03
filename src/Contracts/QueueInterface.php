<?php
/**
 * Created by solly [30.10.17 14:15]
 */

namespace insolita\cqueue\Contracts;

/**
 * Base Queue interface
 */
interface QueueInterface
{
    /**
     * @return string
     */
    public function getName():string;
    
    /**
     * Fill queue
     * @param array $data
     *
     * @return void
     */
    public function fill(array $data);
    
    
    /**
     * RPOPLPUSH behavior, pull item from top, push it to bottom end return
     * @return mixed
     */
    public function next();
    
    public function purgeQueued();
    public function countQueued():int;
    /**
     * List all queued items
     *
     * @param bool $converted //If true, each item will be converted to payload with PayloadConverter
     *
     * @return array
     */
    public function listQueued($converted = false):array;
}
