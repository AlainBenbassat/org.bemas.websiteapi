<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Haseventregistration_spec(&$spec) {
  $spec['civicrm_id']['api.required'] = 1;
  $spec['event_id']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Haseventregistration($params) {
  try {
    $participant = new CRM_Websiteapi_Participant();
    $hasEventRegistration = $participant->hasEventRegistration($params['civicrm_id'], $params['event_id']);

    return civicrm_api3_create_success($hasEventRegistration, $params, 'Bemaswebsite', 'Haseventregistration');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}
