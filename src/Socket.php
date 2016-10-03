<?php

namespace BongRun;

/**
 * Class Socket
 * @package BongRun
 */
class Socket
{
    /**
     * @var array
     */
    public $queueConfig = [];

    /**
     * @var Queue
     */
    private $queue;

    public function __construct($queueConfig)
    {
        $this->queueConfig = $queueConfig;
        $this->queue = new Queue($this->queueConfig);
    }

    /**
     * @param string $eventType
     * @param int|null $eventTypeId
     * @return Event
     */
    public function instance(string $eventType, int $eventTypeId = null){
        return new Event($this->queue->getQueue(), $eventType, $eventTypeId);
    }
}
