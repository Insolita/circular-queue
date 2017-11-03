<?php
/**
 * Created by solly [31.10.17 15:41]
 */

namespace insolita\cqueue;

use Carbon\Carbon;
use insolita\cqueue\Contracts\DelayingInterface;
use insolita\cqueue\Mixins\DelayingTrait;

class CircularQueue extends SimpleCircularQueue implements DelayingInterface
{
    use DelayingTrait;
    
    public function countTotal():int
    {
        return $this->countQueued() + $this->countDelayed();
    }
    
    public function next()
    {
        $this->beforePull();
        return parent::next();
    }
    
    public function pull(int $ttl = 0)
    {
        $this->beforePull();
        $item = $this->storage->listPop($this->queueKey());
        if (!$item) {
            return $this->emptyQueueBehavior->resolve($this);
        } else {
            if ($ttl > 0) {
                $resumeTime = Carbon::now()->timestamp + $ttl;
                $this->storage->zSetPush($this->delayedKey(), $resumeTime, $item);
            }
            return $this->converter->toPayload($item);
        }
    }
    
    protected function beforePull()
    {
        $this->resumeExpired();
    }
}
