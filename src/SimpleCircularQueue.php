<?php
/**
 * Created by solly [30.10.17 22:05]
 */

namespace insolita\cqueue;

use function array_map;
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
    protected $storage;
    
    public function __construct(
        string $name,
        PayloadConverterInterface $converter,
        EmptyQueueBehaviorInterface $emptyQueueBehavior,
        StorageInterface $redis
    ) {
        $this->name = $name;
        $this->converter = $converter;
        $this->emptyQueueBehavior = $emptyQueueBehavior;
        $this->storage = $redis;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function fill(array $data)
    {
        $identities = array_map([$this->converter, 'toIdentity'], $data);
        $this->storage->listPush($this->queueKey(), $identities);
    }
    
    public function purgeQueued()
    {
        $this->storage->delete($this->queueKey());
    }
    
    public function countQueued(): int
    {
        return $this->storage->listCount($this->queueKey());
    }
    
    public function listQueued($converted = false): array
    {
        $list = $this->storage->listItems($this->queueKey());
        if ($converted === false || empty($list)) {
            return $list;
        } else {
            return array_map([$this->converter, 'toPayload'], $list);
        }
    }
    
    public function next()
    {
        $item = $this->storage->listPop($this->queueKey());
        if (!$item) {
            return $this->emptyQueueBehavior->resolve($this);
        } else {
            $this->storage->listPush($this->queueKey(), [$item]);
            return $this->converter->toPayload($item);
        }
    }
    
    protected function queueKey(): string
    {
        return $this->getName() . ':Queue';
    }
}
