<?php
/**
 * Created by solly [30.10.17 22:47]
 */

namespace insolita\cqueue\Contracts;

interface EmptyQueueBehaviorInterface
{
    /**
     * @param \insolita\cqueue\Contracts\QueueInterface $queue
     *
     * @return mixed
     */
    public function resolve($queue);
}
