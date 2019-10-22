<?php
/**
 * Created by solly [30.10.17 23:14]
 */

namespace insolita\cqueue\tests\Unit;

use insolita\cqueue\Behaviors\OnEmptyQueueException;
use insolita\cqueue\Converters\AsIsConverter;
use insolita\cqueue\SimpleCircularQueue;
use insolita\cqueue\Storage\PhpRedisStorage;
use Mockery;
use PHPUnit\Framework\TestCase;

class SimpleCircularQueueTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var \insolita\cqueue\Contracts\QueueInterface $queue
     **/
    private $queue;
    
    /**
     * @var \Mockery\MockInterface $redis
     */
    private $redis;
    
    protected function setUp()
    {
        parent::setUp();
        $this->redis = Mockery::mock(PhpRedisStorage::class);
        $this->queue = new SimpleCircularQueue(
            'test',
            new AsIsConverter(),
            new OnEmptyQueueException(),
            $this->redis
        );
    }
  
    public function testQueueShouldReturnName()
    {
        expect($this->queue->getName())->equals('test');
    }
    
    public function testFill()
    {
        $this->redis->shouldReceive('listPush')->once()->withArgs(['test:Queue', [10, 20, 30]]);
        $this->queue->fill([10, 20, 30]);
    }
 
    public function testPurgeQueued()
    {
        $this->redis->shouldReceive('delete')->once()->withArgs(['test:Queue']);
        $this->queue->purgeQueued();
    }
    
    public function testListQueued()
    {
        $this->redis->shouldReceive('listItems')->andReturn([10, 20, 30]);
        $list = $this->queue->listQueued();
        expect($list)->equals([10, 20, 30]);
    }
    
    public function testCountQueued()
    {
        $this->redis->shouldReceive('listCount')->withArgs(['test:Queue'])->andReturn(123);
        $count = $this->queue->countQueued();
        expect($count)->equals(123);
    }
    
    public function testNext()
    {
        $this->redis->shouldReceive('listPop')->withArgs(['test:Queue'])->andReturn(10);
        $this->redis->shouldReceive('listPush')->withArgs(['test:Queue', [10]]);
        $item = $this->queue->next();
        expect($item)->equals(10);
    }
    
    
    public function testNextOnEmptyQueue()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Queue test is empty');
        $this->redis->shouldReceive('listPop')->withArgs(['test:Queue'])->andReturn(null);
        $this->queue->next();
    }
}
