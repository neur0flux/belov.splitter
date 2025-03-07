<?php

namespace Belov\Splitter\Service;

use Belov\Splitter\Event\OrderWasSplit;
use Bitrix\Main\EventManager;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Throwable;

readonly class OrderSplitter implements OrderSplitterInterface
{
    public function __construct(
        /** @var string[] */
        private array $properties,
        private OrderClonerInterface $orderClonerService,
        private ?EventManager $eventManager = null,
    ) {}

    /**
     * @param Order $order
     * @return Order[]|false
     * @throws Throwable
     */
    public function split(Order $order): array|false
    {
        $groups = $this->splitItems($order->getBasket()->getBasketItems());

        if (count($groups) === 1) {
            return false;
        }

        $splitOrders = [];

        foreach ($groups as $group) {

            $splitOrders[] = $this->orderClonerService->clone($order, $group);
        }

        $this->eventManager?->send(new OrderWasSplit($order, $splitOrders));

        return $splitOrders;
    }

    /**
     * @param BasketItem[] $items
     * @return BasketItem[][]
     * @throws Throwable
     */
    private function splitItems(array $items): array
    {
        $groups = [$items];
        foreach ($this->properties as $property) {

            $nestedGroups = [];

            foreach ($groups as $group) {

                $nestedGroups[] = $this->splitItemsByProperty($group, $property);
            }

            $groups = array_reduce($nestedGroups, function (array $groups, array $nestedGroup) {
                return [...$groups, ...array_values($nestedGroup)];
            }, []);
        }

        return $groups;
    }

    /**
     * @param BasketItem[] $items
     * @return BasketItem[][]
     * @throws Throwable
     */
    private function splitItemsByProperty(array $items, string $property): array
    {
        return array_reduce($items, function (array $groups, BasketItem $item) use ($property) {
            $values = $item->getPropertyCollection()->getPropertyValues();

            $groups[ $values[ $property ]['VALUE'] ][] = $item;
            return $groups;
        }, []);
    }
}