<?php

namespace Belov\Splitter\Service;

use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;

interface OrderClonerInterface
{
    /**
     * @param Order $order
     * @param BasketItem[] $items
     * @return Order
     */
    public function clone(Order $order, array $items): Order;
}