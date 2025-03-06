<?php

namespace Belov\Splitter\Listener;

use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Exception;

/**
 * @Module sale
 * @Event OnSaleOrderBeforeSaved
 */
class OrderSplitterHandler
{
    private static string $firstProperty = 'COLOR_REF';

    private static string $secondProperty = 'SIZES_SHOES';

    /**
     * @throws Exception;
     */
    public static function handle(Event $event): EventResult
    {
        /** @var Order $order */
        $order = $event->getParameter('ENTITY');

        if ($order->getId() !== 0) {
            return new EventResult(EventResult::SUCCESS);
        }

        $groups = self::splitGroups($order->getBasket()->getBasketItems());

        if (count($groups) === 1) {
            return new EventResult(EventResult::SUCCESS);
        }

        foreach ($groups as $group) {

            $splitOrder = self::cloneOrderWithOtherItems($order, $group);

            $splitOrder->doFinalAction(true);
            $splitOrder->save();
        }

        return new EventResult(EventResult::ERROR);
    }

    /**
     * @param BasketItem[] $items
     * @return BasketItem[][]
     * @throws Exception;
     */
    private static function splitGroups(array $items): array
    {
        $nestedGroups = array_reduce($items, function (array $groups, BasketItem $item) {
            $properties = $item->getPropertyCollection()->getPropertyValues();
            $groups[ $properties[ self::$firstProperty ]['VALUE'] ][ $properties[ self::$secondProperty ]['VALUE'] ][] = $item;

            return $groups;
        }, []);

        return array_reduce($nestedGroups, function (array $groups, array $nestedGroup) {
            return [...$groups, ...array_values($nestedGroup)];
        }, []);
    }

    /**
     * @param Order $order
     * @param BasketItem[] $items
     * @return Order
     * @throws Exception
     */
    private static function cloneOrderWithOtherItems(Order $order, array $items): Order
    {
        $paymentCollection = $order->getPaymentCollection();
        foreach ($paymentCollection as $payment) {
            $paySysID = $payment->getPaymentSystemId();
            $paySysName = $payment->getPaymentSystemName();
        }

        $shipmentCollection = $order->getShipmentCollection();
        foreach ($shipmentCollection as $shipment) {
            if ($shipment->isSystem()) continue;
            $shipID = $shipment->getField('DELIVERY_ID');
            $shipName = $shipment->getField('DELIVERY_NAME');
        }

        $splitOrder = Order::create($order->getSiteId(), $order->getUserId());

        $splitOrder->setField('CURRENCY', $order->getCurrency());
        $splitOrder->setPersonTypeId($order->getPersonTypeId());

        $splitBasket = Basket::create($order->getSiteId());

        foreach ($items as $item) {
            $splitItem = $splitBasket->createItem('catalog', $item->getProductId());

            $splitItem->setFields([
                'QUANTITY' => $item->getQuantity(),
                'CURRENCY' => $order->getCurrency(),
                'LID' => $order->getSiteId(),
                'PRODUCT_PROVIDER_CLASS' => CatalogProvider::class,
            ]);

        }

        $splitOrder->setBasket($splitBasket);

        $splitShipmentCollection = $splitOrder->getShipmentCollection();
        $splitShipment = $splitShipmentCollection->createItem();
        $splitShipment->setFields(
            array(
                'DELIVERY_ID' => $shipID,
                'DELIVERY_NAME' => $shipName,
                'CURRENCY' => $order->getCurrency()
            )
        );

        $splitShipmentCollection->calculateDelivery();

        $splitPaymentCollection = $splitOrder->getPaymentCollection();
        $splitPayment = $splitPaymentCollection->createItem();
        $splitPayment->setFields(
            array(
                'PAY_SYSTEM_ID' => $paySysID,
                'PAY_SYSTEM_NAME' => $paySysName,
            )
        );

        $splitPropertyCollection = $splitOrder->getPropertyCollection();

        foreach ($order->getPropertyCollection() as $property) {

            $value = $splitPropertyCollection->getItemByOrderPropertyId($property->getPropertyId());

            $value->setValue($property->getField('VALUE'));
        }

        return $splitOrder;
    }
}