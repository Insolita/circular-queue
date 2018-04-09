<?php
/**
 * Created by solly [30.10.17 14:30]
 */

namespace insolita\cqueue\Contracts;

/**
 *
 */
interface DelayingInterface
{
    /**
     * Pull one item from queue and return resolved payload
     * If ttl greater than 0, mark item as delayed for automatic resume after ttl expire
     * @param int|null $ttl, seconds
     * @return mixed
     */
    public function pull(int $ttl = 0);
    
    /**
     * Return item to queue, possibly after delay
     *
     * @param int $delay , seconds before resume
     * @param $payload
     * @param bool $force (if true, item will added in queue even if it already in queue)
     */
    public function resume($payload, int $delay = 0, $force = false);
    
    /**
     * Return item to queue, after timestamp
     *
     * @param int $timestamp
     * @param $payload
     */
    public function resumeAt($payload, int $timestamp);
    /**
     * Count delayed items
     * @return int
     */
    public function countDelayed():int;
    /**
     * Count delayed + queued items
     * @return int
     */
    public function countTotal():int;
    
    /**
     * List all delayed items
     *
     * @param bool $converted //If true, each item will be converted to payload with PayloadConverter
     *
     * @return array
     */
    public function listDelayed($converted = false):array;
    
    /**
     * Return all delayed items to queue
     */
    public function resumeAllDelayed();
    
    /**
     * Delete all delayed items
     */
    public function purgeDelayed();
}
