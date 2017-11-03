<?php
/**
 * Created by solly [03.11.17 12:18]
 */

namespace insolita\cqueue\tests\Unit;

use insolita\cqueue\Behaviors\OnEmptyQueueException;
use insolita\cqueue\Behaviors\OnEmptyQueueResumeException;
use insolita\cqueue\CircularQueue;
use insolita\cqueue\Converters\AsIsConverter;
use insolita\cqueue\Converters\SerializableConverter;
use insolita\cqueue\Manager;
use insolita\cqueue\SimpleCircularQueue;
use insolita\cqueue\Storage\PredisStorage;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class ManagerTest extends TestCase
{
    public function testConstruct()
    {
        $q1 = new SimpleCircularQueue(
            'alpha',
            new AsIsConverter(),
            new OnEmptyQueueException(),
            new PredisStorage(new Client())
        );
        $q2 = new CircularQueue(
            'beta',
            new SerializableConverter(),
            new OnEmptyQueueResumeException(),
            new PredisStorage(new Client())
        );
        
        $manager = new Manager([$q1, $q2]);
        expect_that($manager->has('alpha'));
        expect_that($manager->has('beta'));
        expect($manager->queue('alpha')->getName())->equals('alpha');
        expect($manager->queue('alpha'))->isNotInstanceOf(CircularQueue::class);
        expect($manager->queue('beta')->getName())->equals('beta');
        expect($manager->queue('beta'))->isInstanceOf(CircularQueue::class);
    }
    
    public function testNotExistedQueue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Queue Foo not registered');
        $manager = new Manager();
        expect_not($manager->has('Foo'));
        $manager->queue('Foo');
    }
    
    public function testAdd()
    {
        $manager = new Manager();
        expect_not($manager->has('Foo'));
        $manager->add(new SimpleCircularQueue(
            'Foo',
            new AsIsConverter(),
            new OnEmptyQueueException(),
            new PredisStorage(new Client())
        ));
        expect_that($manager->has('Foo'));
    }
    
    public function testRemove()
    {
        $manager = new Manager([
            new SimpleCircularQueue(
                'Foo',
                new AsIsConverter(),
                new OnEmptyQueueException(),
                new PredisStorage(new Client())
            ),
        ]);
        expect_that($manager->has('Foo'));
        $manager->remove('Foo');
        expect_not($manager->has('Foo'));
        expect_not($manager->has('Bar'));
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Queue Bar not registered');
        $manager->remove('Bar');
    }
}
