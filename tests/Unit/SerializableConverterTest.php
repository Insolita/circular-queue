<?php
/**
 * Created by solly [02.11.17 23:12]
 */

namespace insolita\cqueue\tests\Unit;

use insolita\cqueue\Behaviors\OnEmptyQueueException;
use insolita\cqueue\CircularQueue;
use insolita\cqueue\Converters\SerializableConverter;
use insolita\cqueue\Storage\PhpRedisStorage;
use Mockery;
use PHPUnit\Framework\TestCase;
use function serialize;

class SerializableConverterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    
    /**
     * @var \insolita\cqueue\CircularQueue $queue
     **/
    private $queue;
    
    /**
     * @var \Mockery\MockInterface $redis
     */
    private $redis;
    
    public function setUp()
    {
        parent::setUp();
        $this->redis = Mockery::mock(PhpRedisStorage::class);
        $this->queue = new CircularQueue(
            'test',
            new SerializableConverter(),
            new OnEmptyQueueException(),
            $this->redis
        );
    }
    
    public function testFill()
    {
        $this->redis->shouldReceive('listPush')->once()->withArgs([
            'test:Queue',
            [
                serialize(['id' => 1, 'name' => 'foo']),
                serialize(['id' => 2, 'name' => 'bar']),
                serialize(['id' => 3, 'name' => 'baz']),
            ],
        ]);
        $this->queue->fill(
            [
                ['id' => 1, 'name' => 'foo'],
                ['id' => 2, 'name' => 'bar'],
                ['id' => 3, 'name' => 'baz'],
            ]
        );
    }
    
    public function testNext()
    {
        $serialized = serialize(['id' => 3, 'name' => 'baz']);
        $this->redis->shouldReceive('zSetExpiredItems')->andReturn([]);
        $this->redis->shouldReceive('listPop')->withArgs(['test:Queue'])->andReturn($serialized);
        $this->redis->shouldReceive('listPush')->withArgs(['test:Queue', [$serialized]]);
        $item = $this->queue->next();
        expect($item)->equals(['id' => 3, 'name' => 'baz']);
    }
    
    public function testPull()
    {
        $this->redis->shouldReceive('zSetExpiredItems')->andReturn([]);
        $this->redis->shouldReceive('listPop')->andReturn(serialize(['id' => 10, 'name' => 'foo']));
        $item = $this->queue->pull();
        expect($item)->equals(['id' => 10, 'name' => 'foo']);
    }
    
    public function testListQueued()
    {
        $this->redis->shouldReceive('listItems')->twice()->andReturn([
            serialize(['id' => 10, 'name' => 'foo']),
            serialize(['id' => 20, 'name' => 'bar']),
        ]);
        $notConverted = $this->queue->listQueued();
        expect($notConverted)->equals([
            serialize(['id' => 10, 'name' => 'foo']),
            serialize(['id' => 20, 'name' => 'bar']),
        ]);
        $converted = $this->queue->listQueued(true);
        expect($converted)->equals([
            ['id' => 10, 'name' => 'foo'],
            ['id' => 20, 'name' => 'bar'],
        ]);
    }
    public function testListDelayed()
    {
        $this->redis->shouldReceive('zSetItems')->twice()->andReturn([
            serialize(['id' => 10, 'name' => 'foo']),
            serialize(['id' => 20, 'name' => 'bar']),
        ]);
        $notConverted = $this->queue->listDelayed();
        expect($notConverted)->equals([
            serialize(['id' => 10, 'name' => 'foo']),
            serialize(['id' => 20, 'name' => 'bar']),
        ]);
        $converted = $this->queue->listDelayed(true);
        expect($converted)->equals([
            ['id' => 10, 'name' => 'foo'],
            ['id' => 20, 'name' => 'bar'],
        ]);
    }
    public function testResumeWithoutDelayForWaited()
    {
        $serialized = serialize(['id' => 3, 'name' => 'baz']);
        $this->redis->shouldReceive('zSetExists')->withArgs(['test:Wait', $serialized])->andReturn(true);
        $this->redis->shouldReceive('moveFromZSetToList')->withArgs(['test:Queue', 'test:Wait', $serialized]);
        $this->queue->resume(['id' => 3, 'name' => 'baz']);
    }
    
    public function testResumeWithoutDelayForNotWaited()
    {
        $serialized = serialize(['id' => 3, 'name' => 'baz']);
        $this->redis->shouldReceive('zSetExists')->withArgs(['test:Wait', $serialized])->andReturn(false);
        $this->redis->shouldReceive('listPush')->withArgs(['test:Queue', [$serialized]]);
        $this->queue->resume(['id' => 3, 'name' => 'baz']);
    }
}
