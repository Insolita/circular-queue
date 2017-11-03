Circular Queue
==============
Circular Queue with redis implementation for distribution of shared data
Useful for resource balancing, parsing

###Install
`composer require insolita/circular-queue`

###Usage

#####SimpleCircularQueue

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
#####CircularQueue

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