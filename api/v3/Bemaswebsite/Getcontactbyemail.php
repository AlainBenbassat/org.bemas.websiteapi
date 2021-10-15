<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Getcontactbyemail_spec(&$spec) {
  $spec['email']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Getcontactbyemail($params) {
  try {
    $contact = new CRM_Websiteapi_Contact();
    $result = $contact->getContactByEmail($params['email']);

    return civicrm_api3_create_success($result, $params, 'Bemaswebsite', 'Getcontactbyemail');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(),$e->getCode());
  }
}
