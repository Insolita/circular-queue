<?php
/**
 * Created by solly [30.10.17 23:14]
 */

namespace insolita\cqueue\tests\Unit;

use Carbon\Carbon;
use insolita\cqueue\Behaviors\OnEmptyQueueException;
use insolita\cqueue\CircularQueue;
use insolita\cqueue\Converters\AsIsConverter;
use insolita\cqueue\Storage\PhpRedisStorage;
use Mockery;
use PHPUnit\Framework\TestCase;

class CircularQueueTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var \Mockery\MockInterface $redis
     */
    private $redis;
    
    /**
     * @var \insolita\cqueue\CircularQueue $queue
     **/
    private $queue;
    
    public function setUp()
    {
        parent::setUp();
        $this->redis = Mockery::mock(PhpRedisStorage::class);
        $this->queue = new CircularQueue(
            'test',
            new AsIsConverter(),
            new OnEmptyQueueException(),
            $this->redis
        );
    }
    
    public function testPurgeDelayed()
    {
        $this->redis->shouldReceive('delete')->once()->withArgs(['test:Wait']);
        $this->queue->purgeDelayed();
    }
    
    public function testListDelayed()
    {
        $this->redis->shouldReceive('zSetItems')->andReturn([10, 20, 30]);
        $list = $this->queue->listDelayed();
        expect($list)->equals([10, 20, 30]);
    }
    
    public function testCountTotal()
    {
        $this->redis->shouldReceive('listCount')->withArgs(['test:Queue'])->andReturn(123);
        $this->redis->shouldReceive('zSetCount')->withArgs(['test:Wait'])->andReturn(234);
        $count = $this->queue->countTotal();
        expect($count)->equals(357);
    }
    
    public function testCountDelayed()
    {
        $this->redis->shouldReceive('zSetCount')->withArgs(['test:Wait'])->andReturn(123);
        $count = $this->queue->countDelayed();
        expect($count)->equals(123);
    }
    
    public function testPull()
    {
        $this->redis->shouldReceive('zSetExpiredItems')->andReturn([]);
        $this->redis->shouldNotReceive('moveFromZSetToList');
        $this->redis->shouldReceive('listPop')->andReturn(10);
        $this->redis->shouldNotReceive('zSetPush');
        $item = $this->queue->pull();
        expect($item)->equals(10);
    }
    
    public function testPullWithResumeExpired()
    {
        $this->redis->shouldReceive('zSetExpiredItems')->andReturn([1, 2, 3]);
        $this->redis->shouldReceive('moveFromZSetToList')->once()->withArgs(['test:Queue', 'test:Wait', 1]);
        $this->redis->shouldReceive('moveFromZSetToList')->once()->withArgs(['test:Queue', 'test:Wait', 2]);
        $this->redis->shouldReceive('moveFromZSetToList')->once()->withArgs(['test:Queue', 'test:Wait', 3]);
        $this->redis->shouldReceive('listPop')->andReturn(10);
        $this->redis->shouldNotReceive('zSetPush');
        $item = $this->queue->pull();
        expect($item)->equals(10);
    }
    
    public function testPullWithTtl()
    {
        Carbon::setTestNow('now');
        $now = Carbon::now()->timestamp;
        $this->redis->shouldReceive('zSetExpiredItems')->andReturn([]);
        $this->redis->shouldNotReceive('moveFromZSetToList');
        $this->redis->shouldReceive('listPop')->andReturn(10);
        $this->redis->shouldReceive('zSetPush')->withArgs(['test:Wait', $now + 200, 10]);
        $item = $this->queue->pull(200);
        expect($item)->equals(10);
    }
    
    public function testPullOnEmptyQueue()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Queue test is empty');
        $this->redis->shouldReceive('zSetExpiredItems')->andReturn([]);
        $this->redis->shouldNotReceive('moveFromZSetToList');
        $this->redis->shouldReceive('listPop')->withArgs(['test:Queue'])->andReturn(null);
        $this->queue->pull();
    }
    
    public function testResumeWithoutDelayForWaited()
    {
        $this->redis->shouldReceive('zSetExists')->withArgs(['test:Wait', 10])->andReturn(true);
        $this->redis->shouldReceive('moveFromZSetToList')->withArgs(['test:Queue', 'test:Wait', 10]);
        $this->redis->shouldNotReceive('listPush');
        $this->redis->shouldNotReceive('zSetPush');
        $this->queue->resume(10);
    }
    
    public function testResumeWithoutDelayForNotWaited()
    {
        $this->redis->shouldReceive('zSetExists')->withArgs(['test:Wait', 10])->andReturn(false);
        $this->redis->shouldNotReceive('moveFromZSetToList');
        $this->redis->shouldReceive('listPush')->withArgs(['test:Queue', [10]]);
        $this->redis->shouldNotReceive('zSetPush');
        $this->queue->resume(10);
    }
    
    public function testResumeWithDelay()
    {
        Carbon::setTestNow('now');
        $delay = 300;
        $timestamp = Carbon::now()->timestamp + 300;
        $this->redis->shouldNotReceive('zSetExists');
        $this->redis->shouldNotReceive('moveFromZSetToList');
        $this->redis->shouldNotReceive('listPush');
        $this->redis->shouldReceive('zSetPush')->withArgs(['test:Wait', $timestamp, 10]);
        $this->queue->resume(10, $delay);
    }
    
    public function testResumeAt()
    {
        Carbon::setTestNow('now');
        $timestamp = Carbon::now()->timestamp + 300;
        $this->redis->shouldNotReceive('zSetExists');
        $this->redis->shouldNotReceive('moveFromZSetToList');
        $this->redis->shouldNotReceive('listPush');
        $this->redis->shouldReceive('zSetPush')->withArgs(['test:Wait', $timestamp, 10]);
        $this->queue->resumeAt(10, $timestamp);
    }
    
    public function testResumeAllDelayed()
    {
        $this->redis->shouldReceive('zSetItems')->andReturn([10, 20]);
        $this->redis->shouldReceive('moveFromZSetToList')->once()->withArgs(['test:Queue', 'test:Wait', 10]);
        $this->redis->shouldReceive('moveFromZSetToList')->once()->withArgs(['test:Queue', 'test:Wait', 20]);
        $this->queue->resumeAllDelayed();
    }
}
