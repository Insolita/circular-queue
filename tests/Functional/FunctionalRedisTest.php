<?php
/**
 * Created by solly [02.11.17 20:15]
 */

namespace insolita\cqueue\tests\Functional;

use Carbon\Carbon;
use insolita\cqueue\Behaviors\OnEmptyQueueException;
use insolita\cqueue\CircularQueue;
use insolita\cqueue\Converters\AsIsConverter;
use insolita\cqueue\Storage\PhpRedisStorage;
use PHPUnit\Framework\TestCase;
use Redis;

class FunctionalRedisTest extends TestCase
{
    /**
     * @var CircularQueue
     */
    private $queue;
    
    protected function setUp()
    {
        parent::setUp();
        $redis = new Redis();
        $redis->connect('127.0.0.1');
        $redis->select(0);
        $storage = new PhpRedisStorage($redis);
        $this->queue = new CircularQueue('testRedis', new AsIsConverter(), new OnEmptyQueueException(), $storage);
        $this->queue->purgeQueued();
        $this->queue->purgeDelayed();
    }
    
    public function testSimpleBehavior()
    {
        expect($this->queue->countQueued())->equals(0);
        expect($this->queue->countDelayed())->equals(0);
        expect($this->queue->countTotal())->equals(0);
        
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        
        expect($this->queue->countQueued())->equals(5);
        expect($this->queue->countDelayed())->equals(0);
        expect($this->queue->countTotal())->equals(5);
        
        $item = $this->queue->next();
        expect($item)->equals('foo');
        expect($this->queue->countQueued())->equals(5);
        $item = $this->queue->next();
        expect($item)->equals('bar');
        expect($this->queue->countQueued())->equals(5);
        $item = $this->queue->next();
        expect($item)->equals('baz');
        expect($this->queue->countQueued())->equals(5);
        $item = $this->queue->next();
        expect($item)->equals('one');
        expect($this->queue->countQueued())->equals(5);
        $item = $this->queue->next();
        expect($item)->equals('two');
        expect($this->queue->countQueued())->equals(5);
        $item = $this->queue->next();
        expect($item)->equals('foo');
        expect($this->queue->countQueued())->equals(5);
        
        $allItems = $this->queue->listQueued();
        expect($allItems)->equals(['foo', 'two', 'one', 'baz', 'bar']);
    }
    
    public function testPullBehavior()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        $one = $this->queue->pull();
        expect($one)->equals('foo');
        expect('pull without ttl not mark item as delayed', $this->queue->countDelayed())->equals(0);
        expect('pull decrease queue', $this->queue->countQueued())->equals(4);
        expect($this->queue->countTotal())->equals(4);
        
        $two = $this->queue->pull(5);
        expect($two)->equals('bar');
        expect('pull with ttl store item in delayed zSet', $this->queue->countDelayed())->equals(1);
        expect($this->queue->countQueued())->equals(3);
        expect($this->queue->countTotal())->equals(4);
    }
    
    public function testPullAutoResume()
    {
        Carbon::setTestNow();
        $now = Carbon::now();
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        $one = $this->queue->pull(10);
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->contains($one);
        Carbon::setTestNow($now->addSeconds(30));
        $two = $this->queue->pull(10);
        expect('item was resumed in queue', $this->queue->listQueued())->contains($one);
        expect('item not in delayed', $this->queue->listDelayed())->notContains($one);
        expect($this->queue->listQueued())->notContains($two);
        expect($this->queue->listDelayed())->contains($two);
    }
    
    public function testResumeNewItem()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        $newItem = 'qqq';
        $this->queue->resume($newItem);
        expect('item was resumed in queue', $this->queue->listQueued())->contains($newItem);
        expect('item not in delayed', $this->queue->listDelayed())->notContains($newItem);
        $newItem = 'qqq2';
        $this->queue->resume($newItem, 30);
        expect('item not resumed in queue', $this->queue->listQueued())->notContains($newItem);
        expect('item in delayed', $this->queue->listDelayed())->contains($newItem);
    }
    
    public function testResumeForItemPulledWithDelay()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        $one = $this->queue->pull(10);
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->contains($one);
        $this->queue->resume($one);
        
        expect('item was resumed in queue', $this->queue->listQueued())->contains($one);
        expect('item not in delayed', $this->queue->listDelayed())->notContains($one);
    }
    public function testEnsureDoubleResumePrevented()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        $one = $this->queue->pull(10);
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->contains($one);
        $this->queue->resume($one);
        $this->queue->resume($one);
        $this->queue->resume($one);
        $this->queue->resume($one);
        expect($this->queue->countQueued())->equals(5);
        $this->queue->resume($one, 0 ,true);
        $this->queue->resume($one, 0 ,true);
        expect('resumed with force flag skip checking unique', $this->queue->countQueued())->equals(7);
    }
    public function testResumeForItemPulledWithoutDelay()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        $one = $this->queue->pull();
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->notContains($one);
        $this->queue->resume($one);
        expect($this->queue->listQueued())->contains($one);
        expect($this->queue->listDelayed())->notContains($one);
    }
    
    public function testManualResumeWithDelayForItemPulledWithoutDelay()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        Carbon::setTestNow();
        $now = Carbon::now();
        $one = $this->queue->pull();
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->notContains($one);
        $this->queue->resume($one, 10000);
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->contains($one);
        Carbon::setTestNow($now->addSeconds(12000));
        $this->queue->next();
        expect($this->queue->listQueued())->contains($one);
        expect($this->queue->listDelayed())->notContains($one);
    }
    
    public function testManualResumeWithDelayForItemPulledWithDelay()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        Carbon::setTestNow();
        $now = Carbon::now();
        $one = $this->queue->pull(10);
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->contains($one);
        $this->queue->resume($one, 10000);
        Carbon::setTestNow($now->addSeconds(200));
        $this->queue->next();
        expect($this->queue->listQueued())->notContains($one);
        expect($this->queue->listDelayed())->contains($one);
        Carbon::setTestNow($now->addSeconds(12000));
        $this->queue->next();
        expect($this->queue->listQueued())->contains($one);
        expect($this->queue->listDelayed())->notContains($one);
    }
    
    public function testResumeAllDelayed()
    {
        $this->queue->fill(['foo', 'bar', 'baz', 'one', 'two']);
        expect($this->queue->listQueued())->equals(['two', 'one', 'baz', 'bar', 'foo']);
        $one = $this->queue->pull(10);
        $two = $this->queue->pull(15);
        $three = $this->queue->pull(3);
        expect($this->queue->countQueued())->equals(2);
        expect($this->queue->countDelayed())->equals(3);
        $this->queue->resumeAllDelayed();
        expect($this->queue->countQueued())->equals(5);
        expect($this->queue->countDelayed())->equals(0);
        expect($this->queue->listQueued())->equals([$two, $one, $three, 'two', 'one']);
    }
}
