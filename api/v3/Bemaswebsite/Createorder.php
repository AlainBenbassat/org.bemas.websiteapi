<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Createorder_spec(&$spec) {
  $spec['order_id']['api.required'] = 1;
  $spec['order_date']['api.required'] = 1;
  $spec['contact_id']['api.required'] = 1;
  $spec['order_status']['api.required'] = 1;
  $spec['total_amount']['api.required'] = 1;
  $spec['products']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Createorder($params) {
  $contact = new CRM_Websiteapi_Order();
  $contactId = $contact->createOrder($params);
  return civicrm_api3_create_success($contactId, $params, 'Bemaswebsite', 'Createorder');
}
