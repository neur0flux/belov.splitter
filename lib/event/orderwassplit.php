<?php

namespace Belov\Splitter\Event;

use Bitrix\Main\Event;
use Bitrix\Sale\Order;

/**
 *
 */
class OrderWasSplit extends Event
{
    private readonly Order $order;

    /**
     * @var Order[]
     */
    private readonly array $splitOrders;

    /**
     * @param Order $order
     * @param Order[] $splitOrders
     */
    public function __construct(Order $order, array $splitOrders)
    {
        $this->order = $order;
        $this->splitOrders = $splitOrders;
        parent::__construct('belov.splitter', 'onOrderWasSplit');
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return Order[]
     */
    public function getSplitOrders(): array
    {
        return $this->splitOrders;
    }
}