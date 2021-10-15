<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Getmembers_spec(&$spec) {
}

function civicrm_api3_bemaswebsite_Getmembers($params) {
  try {
    $member = new CRM_Websiteapi_Member();
    $members = $member->getMembers();

    return civicrm_api3_create_success($members, $params, 'Bemaswebsite', 'Getmembers');
  }
  catch (Exception $e) {
    throw new API_Exception($e->getMessage(),$e->getCode());
  }
}
