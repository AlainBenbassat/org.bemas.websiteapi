<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Getcontactbyuid_spec(&$spec) {
  $spec['uid']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Getcontactbyuid($params) {
  try {
    $contact = new CRM_Websiteapi_Contact();
    $result = $contact->getContactByUid($params['uid']);

    return civicrm_api3_create_success($result, $params, 'Bemaswebsite', 'Getcontactbyuid');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(),$e->getCode());
  }
}
