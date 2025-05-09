<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Getmembership_spec(&$spec) {
  $spec['contact_id']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Getmembership($params) {
  try {
    $member = new CRM_Websiteapi_Member();
    $members = $member->getMembership($params['contact_id']);

    return civicrm_api3_create_success($members, $params, 'Bemaswebsite', 'Getmembership');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(), $e->getCode());
  }
}
