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
     * Push one item to queue
     * @param $payload
     *
     * @return mixed
     */
    public function push($payload);
    
    /**
     * RPOPLPUSH behavior, pull item from top, push it to bottom end return
     * @return mixed
     */
    public function next();
    
    public function purgeQueued();
    public function countQueued():int;
    public function listQueued():array;
}
