<?php
/**
 * Created by solly [30.10.17 22:05]
 */

namespace insolita\cqueue;

use insolita\cqueue\Contracts\EmptyQueueBehaviorInterface;
use insolita\cqueue\Contracts\PayloadConverterInterface;
use insolita\cqueue\Contracts\QueueInterface;
use insolita\cqueue\Contracts\StorageInterface;

class SimpleCircularQueue implements QueueInterface
{
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var \insolita\cqueue\Contracts\PayloadConverterInterface
     */
    protected $converter;
    
    /**
     * @var \insolita\cqueue\Contracts\EmptyQueueBehaviorInterface
     */
    protected $emptyQueueBehavior;
    
    /**
     * @var \insolita\cqueue\Contracts\StorageInterface
     */
    protected $redis;
    
    public function __construct(
        string $name,
        PayloadConverterInterface $converter,
        EmptyQueueBehaviorInterface $emptyQueueBehavior,
        StorageInterface $redis
    ) {
        $this->name = $name;
        $this->converter = $converter;
        $this->emptyQueueBehavior = $emptyQueueBehavior;
        $this->redis = $redis;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function fill(array $data)
    {
        $identities = array_map([$this->converter, 'toIdentity'], $data);
        $this->redis->listPush($this->queueKey(), $identities);
    }
    public function purgeQueued()
    {
        $this->redis->delete($this->queueKey());
    }
    
    public function countQueued():int
    {
        return $this->redis->listCount($this->queueKey());
    }
    
    public function listQueued(): array
    {
        return $this->redis->listItems($this->queueKey());
    }
    
    public function push($item)
    {
        $this->redis->listPush($this->queueKey(), [$this->converter->toIdentity($item)]);
    }
    
    public function next()
    {
        $item = $this->redis->listPop($this->queueKey());
        if (!$item) {
            return $this->emptyQueueBehavior->resolve($this);
        } else {
            $this->redis->listPush($this->queueKey(), [$item]);
            return $this->converter->toPayload($item);
        }
    }

    protected function queueKey(): string
    {
        return $this->getName() . ':Queue';
    }
}
