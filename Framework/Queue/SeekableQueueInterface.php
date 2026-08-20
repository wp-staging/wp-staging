<?php

namespace WPStaging\Framework\Queue;

interface SeekableQueueInterface
{
 
    const MODE_WRITE = 'ab';

 
    const MODE_READ_WRITE = 'rb+';

    public function setup($queueName, $queueMode = SeekableQueueInterface::MODE_READ_WRITE);






    public function isFinished();






    public function dequeue();






    public function enqueue($data);






    public function enqueueMany(array $data = []);






    public function retry($dequeue = true);




    public function reset();




    public function seek($offset);




    public function getOffset();




    public function shutdown();
}
