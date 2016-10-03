<?php

namespace BongRun;

use PhpAmqpLib\Connection\AMQPStreamConnection;

use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * Class Queue
 * @package BongRun
 */
class Queue
{
    public $host = '127.0.0.1';
    public $port = 5672;

    public $vhost = '/';
    public $login = 'guest';
    public $password = 'guest';
    /**
     * name of the default queue
     */
    public $queue;
    /**
     * create the exchange if not exists
     * @var bool
     */
    public $exchange_declare = true;
    /**
     * create the queue if not exists and bind to the exchange
     * @var bool
     */
    public $queue_declare_bind = true;

    public $queue_params = [
        'passive' => false,
        'durable' => true,
        'exclusive' => false,
        'auto_delete' => false,
    ];

    public $exchange_params = [
        'name' => null,
        'type' => 'direct',
        'passive' => false,
        // the exchange will survive server restarts
        'durable' => true,
        'auto_delete' => false,
    ];

    /**
     * @var RabbitMQQueue
     */
    private $queueItem;

    public function __construct($config = null)
    {
        $this->setConfig($config);
        $config = $this->getConfig();
        // create connection with AMQP
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['login'], $config['password'],
            $config['vhost']);

        $this->queueItem = new RabbitMQQueue(
            $connection,
            $config
        );
    }

    /**
     * @return RabbitMQQueue
     */
    public function getQueue(){
        return $this->queueItem;
    }

    private function setConfig($config = null){
        if (!is_null($config)) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
    }

    private function getConfig(){
        $config = [];
        foreach ((array($this)) as $key => $value) {
            $config[($key{0} === "\0") ? substr($key, strpos($key, "\0", 1) + 1) : $key] = $value;
        }
        return $config;
    }
}
