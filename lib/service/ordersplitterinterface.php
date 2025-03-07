<?php

namespace Belov\Splitter\Service;

use Bitrix\Sale\Order;

interface OrderSplitterInterface
{
    /**
     * @param Order $order
     * @return Order[]|false
     */
    public function split(Order $order): array|false;
}