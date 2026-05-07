<?php

return [

    'prices' => [
        'club' => env('STRIPE_PRICE_CLUB', 'price_REPLACE_CLUB'),
        'socio' => env('STRIPE_PRICE_SOCIO', 'price_REPLACE_SOCIO'),
    ],

    'amounts' => [
        'club' => '129.99',
        'socio' => '39.99',
    ],

    'currency' => 'usd',

];
