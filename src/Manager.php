<?php
/**
 * Created by solly [03.11.17 12:06]
 */

namespace insolita\cqueue;

use insolita\cqueue\Contracts\QueueInterface;
use insolita\cqueue\Contracts\DelayingInterface;
use InvalidArgumentException;

class Manager
{
    
    /**
     * @var array|QueueInterface[]|DelayingInterface[]|CircularQueue[]
     */
    private $queues = [];
    
    public function __construct($queues = [])
    {
        if (!empty($queues)) {
            foreach ($queues as $queue) {
                $this->add($queue);
            }
        }
    }
    
    /**
     * @param QueueInterface|CircularQueue $queue
     */
    public function add(QueueInterface $queue)
    {
        $this->queues[$queue->getName()] = $queue;
    }
    
    /**
     * @param string $queueName
     *
     * @throws \InvalidArgumentException
     */
    public function remove(string $queueName)
    {
        if (isset($this->queues[$queueName])) {
            unset($this->queues[$queueName]);
        } else {
            throw new InvalidArgumentException('Queue ' . $queueName . ' not registered');
        }
    }
    
    public function has(string $queueName): bool
    {
        return isset($this->queues[$queueName]);
    }
    
    /**
     * @param string $queueName
     *
     * @return CircularQueue|DelayingInterface|QueueInterface
     * @throws \InvalidArgumentException
     */
    public function queue(string $queueName)
    {
        if (isset($this->queues[$queueName])) {
            return $this->queues[$queueName];
        }

        throw new InvalidArgumentException('Queue ' . $queueName . ' not registered');
    }
}
