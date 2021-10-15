<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Setcontactuid_spec(&$spec) {
  $spec['id']['api.required'] = 1;
  $spec['uid']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Setcontactuid($params) {
  $contact = new CRM_Websiteapi_Contact();
  $contact->setContactUid($params['id'], $params['uid']);
  return civicrm_api3_create_success('OK', $params, 'Bemaswebsite', 'Setcontactuid');
}
