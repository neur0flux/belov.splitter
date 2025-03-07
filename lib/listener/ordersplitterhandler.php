<?php

namespace Belov\Splitter\Listener;

use Belov\Splitter\Exception\FailedToSplitOrderException;
use Belov\Splitter\Service\OrderSplitter;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;
use Bitrix\Sale\ResultError;
use Throwable;


/**
 * @Module sale
 * @Event OnSaleOrderBeforeSaved
 */
class OrderSplitterHandler
{
    /**
     * @throws Throwable
     */
    public static function handle(Event $event): EventResult
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        if ($order->getId() !== 0) {
            return new EventResult(EventResult::SUCCESS);
        }

        $connection = Application::getConnection();
        try {
            /** @var OrderSplitter $orderSplitter */
            $orderSplitter = ServiceLocator::getInstance()->get('belov.order.splitter');
            $splitOrders = $orderSplitter->split($order);

            if ($splitOrders === false) {
                return new EventResult(EventResult::SUCCESS);
            }

            $connection->startTransaction();

            foreach ($splitOrders as $splitOrder) {
                $splitOrder->doFinalAction(true);
                $splitOrder->save();
            }

            $connection->commitTransaction();
        } catch (Throwable $e) {
            $connection->rollbackTransaction();

            return new EventResult(EventResult::ERROR, new ResultError(Loc::getMessage('FAILED_TO_SPLIT_ORDER')));
        }

        return new EventResult(EventResult::ERROR, new ResultError(Loc::getMessage('ORDER_WAS_SPLIT')));
    }
}