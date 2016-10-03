<?php

namespace BongRun;

use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * Class Event
 * @package BongRun
 */
class Event
{
    /**
     * Всем
     */
    const SEND_TYPE_ALL = 'all';
    /**
     * Всем авторизированным пользователям
     */
    const SEND_TYPE_AUTHORIZED = 'authorized';
    /**
     * Всем не авторизорованным пользователям
     */
    const SEND_TYPE_NOT_AUTHORIZED = 'not_authorized';
    /**
     * Только из списка указанным пользователей
     */
    const SEND_TYPE_ON_THE_LIST = 'on_the_list';

    private $eventType;
    private $eventTypeId;
    private $usersIds = [];
    private $sendType = self::SEND_TYPE_AUTHORIZED;
    /**
     * @var bool Обязательно сейчас подписан на событие
     */
    private $nowSigned = true;
    /**
     * Данные для передачи
     * @var array
     */
    private $items = [];
    private $queue;


    /**
     * Event constructor.
     * @param RabbitMQQueue $queue
     * @param string $eventType
     * @param int|null $eventTypeId
     */
    public function __construct($queue, string $eventType, int $eventTypeId = null)
    {
        $this->queue = $queue;
        $this->eventType = $eventType;
        $this->eventTypeId = $eventTypeId;
    }

    /**
     * Обязательно сейчас должен быть подписан на собюытие
     * @return Event
     */
    public function nowSigned() : Event
    {
        $this->nowSigned = true;
        return $this;
    }

    /**
     * В любой момент
     * @return Event
     */
    public function atAnyTime() : Event
    {
        $this->nowSigned = false;
        return $this;
    }

    /**
     * Указание типа отправки (всем, авториз., не авториз, ...)
     * @param string $sendType
     * @return Event
     */
    public function setSendType(string $sendType) : Event
    {
        $this->sendType = $sendType;
        return $this;
    }

    /**
     * Указание пользователей которым отослать
     * @param int|array $usersIds
     * @return Event
     */
    public function setUsers($usersIds) : Event
    {
        if (!is_array($usersIds)) {
            if ($usersIds > 0) {
                $this->usersIds = [$usersIds];
            }
        } else {
            $this->usersIds = $usersIds;
        }
        return $this;
    }

    /**
     * Добавление задачи в очередь
     * @param array|object $items
     * @return Event
     */
    public function add($items = []) : Event
    {
        if (!is_array($items)) {
            if (is_object($items)) {
                $items = $items->toArray();
            }
        }
        if (is_array($items) && array_keys($items) !== range(0, count($items) - 1)) {
            $items = [$items];
        }
        $this->items = array_merge($this->items, $items);
        return $this;
    }

    /**
     * Добавленеие заданий в очередь
     * @return bool
     */
    public function send() : bool
    {
        $time = 0;
        $data = [];
        foreach ($this->items as $item) {
            if (isset($item['time']) && $item['time'] > $time) {
                $time = $item['time'];
            }
            $temp = [
                'type' => $this->eventType,
                'data' => $item,
            ];
            if ($this->eventTypeId) {
                $temp['id'] = $this->eventTypeId;
            }
            $data[] = $temp;
        }
        if (count($data)) {
            $queue = [
                'sendType' => $this->sendType,
                'nowSigned' => $this->nowSigned,
                'time' => $time,
                'data' => $data,
            ];
            switch ($this->sendType) {
                case self::SEND_TYPE_ON_THE_LIST:
                    if (count($this->usersIds)) {
                        $queue['usersIds'] = $this->usersIds;
                    } else {
                        return false;
                    }
                    break;
            }
            $this->queue->push($queue);
            $this->items = [];
            return true;
        }
        return false;
    }
}
