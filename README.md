Circular Queue
==============
Circular Queue with redis implementation for distribution of shared data
Useful for resource balancing, parsing

[![Build Status](https://travis-ci.org/Insolita/circular-queue.svg?branch=master)](https://travis-ci.org/Insolita/circular-queue)[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Insolita/circular-queue/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Insolita/circular-queue/?branch=master)[![SensioLabsInsight](https://insight.sensiolabs.com/projects/53f28cc0-f72a-4c41-adf0-1e776bc2f694/big.png)](https://insight.sensiolabs.com/projects/53f28cc0-f72a-4c41-adf0-1e776bc2f694)


### Install
`composer require insolita/circular-queue`

### Usage

##### SimpleCircularQueue

```php
  $q = new SimpleCircularQueue(
       'queueName',
        new AsIsConverter(),              // insolita\cqueue\Contracts\PayloadConverterInterface
        new OnEmptyQueueException(),      // insolita\cqueue\Contracts\EmptyQueueBehaviorInterface
        new PredisStorage(new Client())   // insolita\cqueue\Contracts\StorageInterface
  );

  $q->fill(['alpha', 'beta', 'gamma', 'delta']);
  $q->next(); //alpha
  $q->next(); //beta
  $q->next(); //gamma
  $q->next(); //delta
  $q->next(); //alpha
  $q->next(); //beta
  $q->next(); //gamma
  $q->countQueued();//4
  $q->purgeQueued();//clear queue
  ...
```

##### CircularQueue

```php
  $q = new CircularQueue(
       'queueName',
        new AsIsConverter(),              // insolita\cqueue\Contracts\PayloadConverterInterface
        new OnEmptyQueueException(),      // insolita\cqueue\Contracts\EmptyQueueBehaviorInterface
        new PredisStorage(new Client())   // insolita\cqueue\Contracts\StorageInterface
  );
    $q->fill(['alpha', 'beta', 'gamma', 'delta']);
    $item = $q->pull(); //alpha - extract item from queue
    $q->resume($item); // resume item in queue

    $item1 = $q->pull(60); //Item will be resumed in queue after 60 seconds
    $item2 = $q->pull();
    $q->resume($item2, 120); //Item will be resumed in queue after 120 seconds
    $item3 = $q->pull();
    $q->resumeAt($item3, time()+100500); //Item will be resumed  after concrete timestamp
    $q->countTotal()   //4
    $q->countQueued()  //1
    $q->countDelayed() //3
    $q->listDelayed()  // ['beta', 'gamma', 'delta']
    $q->resumeAllDelayed(); //Force resume all delayed in queue
    $q->purgeDelayed(); //Remove all delayed
```

#### Manager

```php
   $q1 = new CircularQueue(
           'firstQueue',
            new SerializableConverter(),
            new OnEmptyQueueException(),
            new PhpRedisStorage(new \Redis())
         );
   $manager = new Manager([$q1]);
   $manager->add(new CircularQueue(
           'secondQueue',
            new SerializableConverter(),
            new OnEmptyQueueException(),
            new PhpRedisStorage(new \Redis())
   ));
   
   $manager->has('secondQueue'); //true
   $manager->has('fooQueue'); //false


   $manager->queue('firstQueue')->fill([...]);
   $manager->queue('secondQueue')->fill([...]);
   ...
   $manager->remove('firstQueue');
   $manager->remove('secondQueue');

```
