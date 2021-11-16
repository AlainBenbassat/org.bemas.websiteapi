<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Createcontact_spec(&$spec) {
  $spec['email']['api.required'] = 1;
  $spec['uid']['api.required'] = 1;
}

function civicrm_api3_bemaswebsite_Createcontact($params) {
  $contact = new CRM_Websiteapi_Contact();
  $contactId = $contact->createContact($params['email'], $params['uid']);
  return civicrm_api3_create_success($contactId, $params, 'Bemaswebsite', 'Createcontact');
}
