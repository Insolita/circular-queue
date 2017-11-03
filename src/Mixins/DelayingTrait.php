<?php
/**
 * Created by solly [30.10.17 23:36]
 */

namespace insolita\cqueue\Mixins;

use Carbon\Carbon;

/**
 * @mixin \insolita\cqueue\CircularQueue
 **/
trait DelayingTrait
{
    
    public function resume($payload, int $delay = 0)
    {
        $identity = $this->converter->toIdentity($payload);
        if ($delay === 0) {
            if ($this->redis->zSetExists($this->delayedKey(), $identity)) {
                $this->redis->moveFromZSetToList($this->queueKey(), $this->delayedKey(), $identity);
            } else {
                $this->redis->listPush($this->queueKey(), [$identity]);
            }
        } else {
            $timestamp = Carbon::now()->timestamp + $delay;
            $this->redis->zSetPush($this->delayedKey(), $timestamp, $identity);
        }
    }
    public function resumeAt($payload, int $timestamp)
    {
        $identity = $this->converter->toIdentity($payload);
        $this->redis->zSetPush($this->delayedKey(), $timestamp, $identity);
    }
    public function countDelayed(): int
    {
        return $this->redis->zSetCount($this->delayedKey());
    }
    
    public function listDelayed($converted = false): array
    {
        $list = $this->redis->zSetItems($this->delayedKey());
        if ($converted === false || empty($list)) {
            return $list;
        } else {
            return array_map([$this->converter, 'toPayload'], $list);
        }
    }
    
    public function resumeAllDelayed()
    {
        $inUse = $this->listDelayed();
        if (!empty($inUse)) {
            foreach ($inUse as $identity) {
                $this->redis->moveFromZSetToList($this->queueKey(), $this->delayedKey(), $identity);
            }
        }
    }
    
    public function purgeDelayed()
    {
        $this->redis->delete($this->delayedKey());
    }
    
    protected function listExpired($expireTime): array
    {
        return $this->redis->zSetExpiredItems($this->delayedKey(), $expireTime);
    }
    
    protected function resumeExpired()
    {
        $inUseExpired = $this->listExpired(Carbon::now()->timestamp);
        if (!empty($inUseExpired)) {
            foreach ($inUseExpired as $identity) {
                $this->redis->moveFromZSetToList($this->queueKey(), $this->delayedKey(), $identity);
            }
        }
    }
    
    protected function delayedKey(): string
    {
        return $this->getName() . ':Wait';
    }
}
