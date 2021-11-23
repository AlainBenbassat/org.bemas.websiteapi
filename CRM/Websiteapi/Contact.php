<?php

class CRM_Websiteapi_Contact {
  public function getContactByEmail($email) {
    $sql = "
      select
        c.id
      from
        civicrm_contact c
      inner join
        civicrm_email e on e.contact_id = c.id and e.is_primary = 1
      where
        e.email = %1
      and
        c.is_deleted = 0
      order by
        c.id
    ";
    $sqlParams = [
      1 => [$email, 'String'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      return $this->getContactById($dao->id);
    }
    else {
      return [];
    }
  }

  public function getContactByUid($uid) {
    $result = civicrm_api3('Contact', 'findbyidentity', [
      'identifier_type' => 1,
      'identifier' => $uid,
    ]);
    if ($result['is_error'] == 0 && $result['count'] > 0) {
      $contact = reset($result['values']);
      return $this->getContactById($contact['id']);
    }
    else {
      return [];
    }
  }

  public function getContactById($contactId) {
    $sql = "
      select
        c.id,
        ch.identifier uid,
        c.first_name,
        c.last_name,
        substring(c.preferred_language, 1, 2) language_code,
        c.job_title,
        e.email,
        p.phone,
        c.organization_name
      from
        civicrm_contact c
      left outer join
        civicrm_email e on e.contact_id = c.id and e.is_primary = 1
      left outer join
        civicrm_phone p on p.contact_id = c.id and p.is_primary = 1
      left outer join
        civicrm_value_contact_id_history ch on ch.entity_id = c.id and identifier_type = 1
      where
        c.is_deleted = 0
      and
        c.id = %1
    ";
    $sqlParams = [
      1 => [$contactId, 'Integer'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    if ($dao->fetch()) {
      $member = new CRM_Websiteapi_Member();

      $contact[] = [
        'id' => $dao->id,
        'uid' => $dao->uid,
        'first_name' => $dao->first_name,
        'last_name' => $dao->last_name,
        'email' => $dao->email,
        'phone' => $dao->phone,
        'language_code' => $dao->language_code,
        'organization_name' => $dao->organization_name,
        'job_title' => $dao->job_title,
        'is_member' => $member->isMember($contactId),
      ];
      return $contact;
    }
    else {
      return [];
    }
  }

  public function setContactUid($contactId, $uid) {
    $result = $this->getContactByUid($uid);
    if (count($result) > 0) {
      throw new Exception('uid already assigned', 999);
    }

    $result = civicrm_api3('Contact', 'addidentity', [
      'contact_id' => $contactId,
      'identifier_type' => 1,
      'identifier' => $uid,
    ]);
  }

  public function createContact($apiParams) {
    $contact = $this->getContactByUid($apiParams['uid']);
    if ($contact) {
      return $contact[0]['id'];
    }

    $params = [
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => $apiParams['first_name'],
      'last_name' => $apiParams['last_name'],
      'job_title' => $apiParams['job_title'],
      'preferred_language' => $this->addCountryCodeToLanguageCode($apiParams['language_code']),
    ];

    $result = civicrm_api3('Contact', 'create', $params);
    $contactId = $result['id'];

    $this->setEmail($contactId, $apiParams['email']);
    $this->setContactUid($contactId, $apiParams['uid']);

    if ($apiParams['phone']) {
      $this->setPhone($contactId, $apiParams['phone']);
    }

    if ($apiParams['organization_name']) {
      $this->setEmployer($contactId, $apiParams['organization_name']);
    }

    return $contactId;
  }

  public function setEmail($contactId, $email) {
    $params = [
      'sequential' => 1,
      'email' => $email,
      'contact_id' => $contactId,
      'location_type_id' => 3,
    ];
    $result = civicrm_api3('Email', 'create', $params);
  }

  public function setPhone($contactId, $phone) {
    $params = [
      'sequential' => 1,
      'phone' => $phone,
      'contact_id' => $contactId,
      'location_type_id' => 3,
      'phone_type_id' => 1,
    ];
    $result = civicrm_api3('Phone', 'create', $params);
  }

  public function setEmployer($contactId, $company) {
    $params = [
      'entity_id' => $contactId,
      'custom_74' => $company,
    ];
    civicrm_api3('CustomValue', 'create', $params);
  }

  private function addCountryCodeToLanguageCode($languageCode) {
    if ($languageCode == 'nl') {
      return 'nl_NL';
    }
    if ($languageCode == 'fr') {
      return 'fr_FR';
    }
    if ($languageCode == 'en') {
      return 'en_US';
    }
    else {
      return '';
    }
  }

}
