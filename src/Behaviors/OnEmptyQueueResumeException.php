<?php
/**
 * Created by solly [30.10.17 22:22]
 */

namespace insolita\cqueue\Behaviors;

use Exception;
use insolita\cqueue\Contracts\DelayingInterface;
use insolita\cqueue\Contracts\EmptyQueueBehaviorInterface;
use insolita\cqueue\Contracts\QueueInterface;

/**
 *
 */
class OnEmptyQueueResumeException implements EmptyQueueBehaviorInterface
{
    
    /**
     * @param QueueInterface|\insolita\cqueue\Contracts\DelayingInterface $queue
     *
     * @return void
     * @throws \Exception
     */
    public function resolve($queue)
    {
        if ($queue instanceof DelayingInterface) {
            $queue->resumeAllDelayed();
        }
        if ($queue->countQueued() === 0) {
            throw new Exception('Queue ' . $queue->getName() . ' is empty');
        }
    }
}
