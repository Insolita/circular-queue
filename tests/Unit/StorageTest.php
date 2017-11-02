<?php
/**
 * Created by solly [02.11.17 19:15]
 */

namespace insolita\cqueue\tests\Unit;

use insolita\cqueue\Storage\PhpRedisStorage;
use insolita\cqueue\Storage\PredisStorage;
use Mockery;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Redis;

class StorageTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    
    /**
     * @var \Redis|\Mockery\MockInterface $phpRedis
     */
    private $phpRedis;
    
    /**
     * @var Client|\Mockery\MockInterface $predis
     */
    private $predis;
    
    /**
     * @var PhpRedisStorage
     */
    private $phpRedisStorage;
    
    /**
     * @var PredisStorage
     */
    private $predisStorage;
    
    public function setUp()
    {
        parent::setUp();
        $this->phpRedis = Mockery::mock(Redis::class);
        $this->predis = Mockery::mock(Client::class);
        $this->predisStorage = new PredisStorage($this->predis);
        $this->phpRedisStorage = new PhpRedisStorage($this->phpRedis);
    }
    
    public function testDelete()
    {
        $this->predis->shouldReceive('del')->withArgs([['key']]);
        $this->predisStorage->delete('key');
        $this->phpRedis->shouldReceive('del')->withArgs(['key']);
        $this->phpRedisStorage->delete('key');
    }
    
    public function testListCount()
    {
        $this->predis->shouldReceive('llen')->withArgs(['key']);
        $this->predisStorage->listCount('key');
        $this->phpRedis->shouldReceive('lLen')->withArgs(['key']);
        $this->phpRedisStorage->listCount('key');
    }
    
    public function testListItems()
    {
        $this->predis->shouldReceive('lrange')->withArgs(['key', 0, -1])->andReturn([]);
        $this->predisStorage->listItems('key');
        $this->phpRedis->shouldReceive('lrange')->withArgs(['key', 0, -1])->andReturn([]);
        $this->phpRedisStorage->listItems('key');
    }
    
    public function testListPop()
    {
        $this->predis->shouldReceive('rpop')->withArgs(['key']);
        $this->predisStorage->listPop('key');
        $this->phpRedis->shouldReceive('rPop')->withArgs(['key']);
        $this->phpRedisStorage->listPop('key');
    }
    
    public function testListPush()
    {
        $this->predis->shouldReceive('lpush')->withArgs(['key', ['foo', 'bar']]);
        $this->predisStorage->listPush('key', ['foo', 'bar']);
        $this->phpRedis->shouldReceive('lPush')->withArgs(['key', 'foo']);
        $this->phpRedis->shouldReceive('lPush')->withArgs(['key', 'bar']);
        $this->phpRedisStorage->listPush('key', ['foo', 'bar']);
    }
    
    public function testZSetCount()
    {
        $this->predis->shouldReceive('zcard')->withArgs(['key']);
        $this->predisStorage->zSetCount('key');
        $this->phpRedis->shouldReceive('zCard')->withArgs(['key']);
        $this->phpRedisStorage->zSetCount('key');
    }
    
    public function testZSetItems()
    {
        $this->predis->shouldReceive('zrange')->withArgs(['key', 0, -1])->andReturn([]);
        $this->predisStorage->zSetItems('key');
        $this->phpRedis->shouldReceive('zRange')->withArgs(['key', 0, -1])->andReturn([]);
        $this->phpRedisStorage->zSetItems('key');
    }
    
    public function testZSetExpiredItems()
    {
        $time = time();
        $this->predis->shouldReceive('zrevrangebyscore')->withArgs(['key', $time, '-inf'])->andReturn([]);
        $this->predisStorage->zSetExpiredItems('key', $time);
        $this->phpRedis->shouldReceive('zRevRangeByScore')->withArgs(['key', $time, '-inf'])->andReturn([]);
        $this->phpRedisStorage->zSetExpiredItems('key', $time);
    }
    
    public function testZSetPush()
    {
        $this->predis->shouldReceive('zadd')->withArgs(['key', ['foo' => 100]]);
        $this->predisStorage->zSetPush('key', 100, 'foo');
        $this->phpRedis->shouldReceive('zAdd')->withArgs(['key', 100, 'foo']);
        $this->phpRedisStorage->zSetPush('key', 100, 'foo');
    }
    
    public function testZSetRem()
    {
        $this->predis->shouldReceive('zrem')->withArgs(['key', 'foo']);
        $this->predisStorage->zSetRem('key', 'foo');
        $this->phpRedis->shouldReceive('zRem')->withArgs(['key', 'foo']);
        $this->phpRedisStorage->zSetRem('key', 'foo');
    }
    
    public function testZSetExists()
    {
        $this->predis->shouldReceive('zScore')->withArgs(['key', 'foo']);
        $this->predisStorage->zSetExists('key', 'foo');
        $this->phpRedis->shouldReceive('zScore')->withArgs(['key', 'foo']);
        $this->phpRedisStorage->zSetExists('key', 'foo');
    }
    
    public function testMoveFromZSetToListExisted()
    {
        $this->predis->shouldReceive('zrem')->withArgs(['zkey', 'foo'])->andReturn(1);
        $this->predis->shouldReceive('lpush')->withArgs(['key', ['foo']]);
        $this->predisStorage->moveFromZSetToList('key', 'zkey', 'foo');
        $this->phpRedis->shouldReceive('zRem')->withArgs(['zkey', 'foo'])->andReturn(1);
        $this->phpRedis->shouldReceive('lPush')->withArgs(['key', 'foo']);
        $this->phpRedisStorage->moveFromZSetToList('key', 'zkey', 'foo');
    }
    
    public function testMoveFromZSetToListAbsent()
    {
        $this->predis->shouldReceive('zrem')->withArgs(['zkey', 'foo'])->andReturn(0);
        $this->predis->shouldNotReceive('lpush');
        $this->predisStorage->moveFromZSetToList('key', 'zkey', 'foo');
        $this->phpRedis->shouldReceive('zRem')->withArgs(['zkey', 'foo'])->andReturn(0);
        $this->phpRedis->shouldNotReceive('lPush');
        $this->phpRedisStorage->moveFromZSetToList('key', 'zkey', 'foo');
    }
}
