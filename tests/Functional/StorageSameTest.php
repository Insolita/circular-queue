<?php
/**
 * Created by solly [03.11.17 13:32]
 */

namespace insolita\cqueue\tests\Functional;

use insolita\cqueue\Storage\PhpRedisStorage;
use insolita\cqueue\Storage\PredisStorage;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Redis;
use function time;

class StorageSameTest extends TestCase
{
    /**
     * @var \insolita\cqueue\Contracts\StorageInterface
     */
    private $predisStorage;
    
    /**
     * @var \insolita\cqueue\Contracts\StorageInterface
     */
    private $phpRedisStorage;
    
    public function setUp()
    {
        $predis = new Client([
            'host' => '127.0.0.1',
        ]);
        $predis->select(0);
        $this->predisStorage = new PredisStorage($predis);
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->select(0);
        $this->phpRedisStorage = new PhpRedisStorage($redis);
        $this->phpRedisStorage->delete($this->listKey());
        $this->phpRedisStorage->delete($this->zSetKey());
        parent::setUp();
    }
    
    public function testEmptyList()
    {
        $this->predisStorage->listPush($this->listKey(), []);
        $this->phpRedisStorage->listPush($this->listKey(), []);
        $predisList = $this->predisStorage->listItems($this->listKey());
        $phpRedisList = $this->phpRedisStorage->listItems($this->listKey());
        expect($predisList)->equals($phpRedisList);
        $predisCount = $this->predisStorage->listCount($this->listKey());
        $phpRedisCount = $this->predisStorage->listCount($this->listKey());
        expect($predisList)->equals($phpRedisList);
        expect($predisCount)->equals($phpRedisCount);
    }
    
    public function testList()
    {
        $this->predisStorage->listPush($this->listKey(), ['alpha', 'beta', 'gamma']);
        $this->phpRedisStorage->listPush($this->listKey(), ['foo', 'bar', 'baz']);
        $predisList = $this->predisStorage->listItems($this->listKey());
        $phpRedisList = $this->phpRedisStorage->listItems($this->listKey());
        $predisCount = $this->predisStorage->listCount($this->listKey());
        $phpRedisCount = $this->predisStorage->listCount($this->listKey());
        expect($predisList)->equals($phpRedisList);
        expect($predisCount)->equals($phpRedisCount);
    }
    
    public function testEmptyZSet()
    {
        $time = time();
        $predisList = $this->predisStorage->zSetItems($this->zSetKey());
        $phpRedisList = $this->phpRedisStorage->zSetItems($this->zSetKey());
        expect($predisList)->equals($phpRedisList);
        $predisExpired = $this->predisStorage->zSetExpiredItems($this->zSetKey(), $time);
        $phpRedisExpired = $this->phpRedisStorage->zSetExpiredItems($this->zSetKey(), $time);
        expect($predisExpired)->equals($phpRedisExpired);
        $phpRedisExist = $this->phpRedisStorage->zSetExists($this->zSetKey(), 'foo');
        $predisExist = $this->phpRedisStorage->zSetExists($this->zSetKey(), 'foo');
        expect($phpRedisExist)->equals($predisExist);
        $phpRedisExist = $this->phpRedisStorage->zSetExists('dummy', 'foo');
        $predisExist = $this->phpRedisStorage->zSetExists('dummy', 'foo');
        expect($phpRedisExist)->equals($predisExist);
    }
    
    public function testZSet()
    {
        $time = time();
        $this->predisStorage->zSetPush($this->zSetKey(), $time - 1000, 'alpha');
        $this->predisStorage->zSetPush($this->zSetKey(), $time - 500, 'beta');
        $this->predisStorage->zSetPush($this->zSetKey(), $time + 500, 'gamma');
        $this->phpRedisStorage->zSetPush($this->zSetKey(), $time - 1000, 'foo');
        $this->phpRedisStorage->zSetPush($this->zSetKey(), $time - 500, 'bar');
        $this->phpRedisStorage->zSetPush($this->zSetKey(), $time + 500, 'baz');
        
        $predisList = $this->predisStorage->zSetItems($this->zSetKey());
        $phpRedisList = $this->phpRedisStorage->zSetItems($this->zSetKey());
        expect($predisList)->equals($phpRedisList);
        
        $predisExpired = $this->predisStorage->zSetExpiredItems($this->zSetKey(), $time);
        $phpRedisExpired = $this->phpRedisStorage->zSetExpiredItems($this->zSetKey(), $time);
        expect($predisExpired)->equals($phpRedisExpired);
    
        $phpRedisExist = $this->phpRedisStorage->zSetExists($this->zSetKey(), 'qwe');
        $predisExist = $this->phpRedisStorage->zSetExists($this->zSetKey(), 'qwe');
        expect($phpRedisExist)->equals($predisExist);
    
        $phpRedisExist = $this->phpRedisStorage->zSetExists($this->zSetKey(), 'foo');
        $predisExist = $this->phpRedisStorage->zSetExists($this->zSetKey(), 'foo');
        expect($phpRedisExist)->equals($predisExist);
    }
    
    protected function listKey()
    {
        return 'TestList';
    }
    
    protected function zSetKey()
    {
        return 'TestZSet';
    }
}
