<?php

$orderChiara = [
  'order_id' => 5628,
  'order_date' => '2024-11-29 15:11',
  'mail' => 'hello@stijndufromont.be',
  'payment_information' => [
    'first_name' => 'Stijn',
    'last_name' => 'Dufromont',
    'company]' => '',
    'address' => [
      'country' => 'BE',
      'line1' => 'Vuurstokerstraat 10',
      'line2' => '',
      'postal_code' => '9070',
      'locality' => 'Heusden, Destelbergen',
    ],
  ],
  'order_status' => 'paid',
  'total_amount' => 0,
  'order_items_amount' => 1595,
  'products' => [
    [
      'product_type' => 'event',
      'product_sku' => 'T241203V',
      'product_id' => 1933,
      'product_title' => 'Voorraadbeheersing van Spare parts',
      'unit_price' => 1595.000000,
      'quantity' => 1.00,
      'total_amount' => 1595.000000,
      'adjustments' => [
        -1595
      ],
      'participants' => [
        [
          'type' => 'myself',
          'preferred_language' => 'nl',
          'first_name' => 'Stijn',
          'last_name' => 'Dufromont',
          'email' => 'hello@stijndufromont.be',
          'function' => '',
          'current_employer' => '',
          'telephone' => '+0487990036',
          'diet' => '',
        ],
      ],
    ],
  ],
  'coupons' => [
      [
        'admin_name' => 'Gratis deelnemer Voorraadbeheersing van spare parts',
        'code' => 'dolor-sit',
      ],
    ],
  'adjustments' => [
    [
      'label' => 'BTW',
      'amount' => 334.95,
    ],
    [
      'label' => 'VAT discount',
      'amount' => -334.95,
    ],
  ],
];
echo print_r($orderChiara, TRUE);exit;
//$order = new CRM_Websiteapi_Order();
//$order->createOrder($orderChiara);

