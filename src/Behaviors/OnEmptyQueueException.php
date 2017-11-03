<?php
/**
 * Created by solly [30.10.17 22:22]
 */

namespace insolita\cqueue\Behaviors;

use Exception;
use insolita\cqueue\Contracts\EmptyQueueBehaviorInterface;

/**
 *
 */
class OnEmptyQueueException implements EmptyQueueBehaviorInterface
{
    
    /**
     * @param \insolita\cqueue\Contracts\QueueInterface $queue
     *
     * @return void
     * @throws \Exception
     */
    public function resolve($queue)
    {
        throw new Exception('Queue ' . $queue->getName() . ' is empty');
    }
}
