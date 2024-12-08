<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Getcertificates_spec(&$spec) {
  $spec['civicrm_id']['api.required'] = 1;
  $spec['language']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Getcertificates($params) {
  try {
    $participant = new CRM_Websiteapi_Participant();
    $eventRegistrations = $participant->getCertificates($params['civicrm_id'], $params['language']);

    return civicrm_api3_create_success($eventRegistrations, $params, 'Bemaswebsite', 'Getcertificates');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}
