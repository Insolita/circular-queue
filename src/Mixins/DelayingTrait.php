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
            if ($this->storage->zSetExists($this->delayedKey(), $identity)) {
                $this->storage->moveFromZSetToList($this->queueKey(), $this->delayedKey(), $identity);
            } else {
                $this->storage->listPush($this->queueKey(), [$identity]);
            }
        } else {
            $timestamp = Carbon::now()->timestamp + $delay;
            $this->storage->zSetPush($this->delayedKey(), $timestamp, $identity);
        }
    }
    public function resumeAt($payload, int $timestamp)
    {
        $identity = $this->converter->toIdentity($payload);
        $this->storage->zSetPush($this->delayedKey(), $timestamp, $identity);
    }
    public function countDelayed(): int
    {
        return $this->storage->zSetCount($this->delayedKey());
    }
    
    public function listDelayed($converted = false): array
    {
        $list = $this->storage->zSetItems($this->delayedKey());
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
                $this->storage->moveFromZSetToList($this->queueKey(), $this->delayedKey(), $identity);
            }
        }
    }
    
    public function purgeDelayed()
    {
        $this->storage->delete($this->delayedKey());
    }
    
    protected function listExpired($expireTime): array
    {
        return $this->storage->zSetExpiredItems($this->delayedKey(), $expireTime);
    }
    
    protected function resumeExpired()
    {
        $inUseExpired = $this->listExpired(Carbon::now()->timestamp);
        if (!empty($inUseExpired)) {
            foreach ($inUseExpired as $identity) {
                $this->storage->moveFromZSetToList($this->queueKey(), $this->delayedKey(), $identity);
            }
        }
    }
    
    protected function delayedKey(): string
    {
        return $this->getName() . ':Wait';
    }
}
