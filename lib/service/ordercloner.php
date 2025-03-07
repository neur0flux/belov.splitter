<?php

namespace Belov\Splitter\Service;

use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Shipment;
use Throwable;

readonly class OrderCloner implements OrderClonerInterface
{
    /**
     * @param Order $order
     * @param BasketItem[] $items
     * @return Order
     * @throws Throwable
     */
    public function clone(Order $order, array $items): Order
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

        $clone = Order::create($order->getSiteId(), $order->getUserId());
        $clone->setField('CURRENCY', $order->getCurrency());
        $clone->setPersonTypeId($order->getPersonTypeId());

        $basket = Basket::create($order->getSiteId());

        foreach ($items as $item) {
            $cloneItem = $basket->createItem($item->getField('MODULE'), $item->getProductId());
            $cloneItem->setFields([
                'QUANTITY' => $item->getQuantity(),
                'CURRENCY' => $order->getCurrency(),
                'LID' => $order->getSiteId(),
                'PRODUCT_PROVIDER_CLASS' => $item->getProvider(),
            ]);
        }
        $clone->setBasket($basket);

        $cloneShipmentCollection = $clone->getShipmentCollection();
        $cloneShipment = $cloneShipmentCollection->createItem();
        $cloneShipment->setFields(
            array(
                'DELIVERY_ID' => $shipID,
                'DELIVERY_NAME' => $shipName,
                'CURRENCY' => $order->getCurrency()
            )
        );

        $cloneShipmentCollection->calculateDelivery();

        $clonePaymentCollection = $clone->getPaymentCollection();
        $clonePayment = $clonePaymentCollection->createItem();
        $clonePayment->setFields(
            array(
                'PAY_SYSTEM_ID' => $paySysID,
                'PAY_SYSTEM_NAME' => $paySysName,
            )
        );


        $clonePropertyCollection = $clone->getPropertyCollection();

        foreach ($order->getPropertyCollection() as $property) {

            $clonePropertyCollection->getItemByOrderPropertyId($property->getPropertyId())
                ?->setValue($property->getField('VALUE'));
        }

        $discountData = $order->getDiscount()->getApplyResult();

        if (!empty($discountData) && isset($discountData['DISCOUNT_LIST'])) {
            $cloneDiscount = $clone->getDiscount();
            $cloneDiscount->setApplyResult($discountData);
            $cloneDiscount->calculate();
        }

        return $clone;
    }
}