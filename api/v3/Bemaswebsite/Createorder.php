<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Createorder_spec(&$spec) {
  $spec['order_id']['api.required'] = 1;
  $spec['order_date']['api.required'] = 1;
  $spec['order_status']['api.required'] = 1;
  $spec['total_amount']['api.required'] = 1;
  $spec['products']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Createorder($params) {
  try {
    $order = new CRM_Websiteapi_Order();
    $order->createOrder($params);
    return civicrm_api3_create_success('OK', $params, 'Bemaswebsite', 'Createorder');
  }
  catch (Exception $e) {
    Civi::log()->error($e->getMessage());
    Civi::log()->error(print_r($e->getTrace(), TRUE));

    throw new API_Exception($e->getMessage(), 999);
  }
}
