<?php

use Belov\Splitter\Service\OrderCloner;
use Belov\Splitter\Service\OrderSplitter;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\EventManager;

return [
    'services' => [
        'value' => [
            'belov.order.splitter' => [
                'constructor' => static function () {
                    $properties = Option::get('belov.splitter', 'properties');

                    return new OrderSplitter(
                        explode(';', $properties),
                        ServiceLocator::getInstance()->get('belov.order.cloner'),
                        EventManager::getInstance(),
                    );
                },
            ],
            'belov.order.cloner' => [
                'className' => OrderCloner::class,
            ],
        ],
    ],
];