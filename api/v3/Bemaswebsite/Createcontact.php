<?php
use CRM_Websiteapi_ExtensionUtil as E;

function _civicrm_api3_bemaswebsite_Createcontact_spec(&$spec) {
  $spec['email']['api.required'] = 1;
  $spec['uid']['api.required'] = 1;
  $spec['first_name']['api.required'] = 1;
  $spec['last_name']['api.required'] = 1;
  $spec['language_code']['api.required'] = 1;
  $spec['phone']['api.required'] = 0;
  $spec['job_title']['api.required'] = 0;
  $spec['company']['api.required'] = 0;
}

function civicrm_api3_bemaswebsite_Createcontact($params) {
  $contact = new CRM_Websiteapi_Contact();
  $contactId = $contact->createContact($params);
  return civicrm_api3_create_success($contactId, $params, 'Bemaswebsite', 'Createcontact');
}
