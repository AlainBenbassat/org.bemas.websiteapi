<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Getparticipantscount_spec(&$spec) {
  $spec['event_id']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Getparticipantscount($params) {
  try {
    $event = new CRM_Websiteapi_Event();
    $numEventRegistrations = $event->getParticipantsCount($params['event_id']);

    return civicrm_api3_create_success($numEventRegistrations, $params, 'Bemaswebsite', 'Getparticipantscount');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}
