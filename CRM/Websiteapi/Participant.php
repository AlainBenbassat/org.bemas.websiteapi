<?php

class CRM_Websiteapi_Participant {
  public function createEventRegistration($orderHeader, $product, $participant) {
    $contactId = $this->getContactId($participant);
    $eventId = $product->product_id;

    if (!$this->isRegistered($contactId, $eventId)) {
      $participantId = $this->saveEventRegistration($orderHeader, $product, $contactId, $eventId);

      if ($product->unit_price > 0) {
        $this->saveEventPayment($orderHeader, $product, $contactId, $eventId, $participantId);
      }

      if (!empty($product->notes)) {
        $this->saveRegistrationNotes($participantId, $product->notes);
      }
    }

    return $contactId;
  }

  public function fillRegisteredBy($eventId, $mainContactId, $contactIdParticipants) {
    $mainContactParticipantId = $this->getParticipantId($eventId, $mainContactId);
    if (!$mainContactParticipantId) {
      $mainContactParticipantId = $this->registerAsTrainingResponsible($eventId, $mainContactId);
    }

    foreach ($contactIdParticipants as $contactIdParticipant) {
      if ($contactIdParticipant != $mainContactId) {
        $participantId = $this->getParticipantId($eventId, $contactIdParticipant);
        $this->setRegisterBy($mainContactParticipantId, $participantId);
      }
    }
  }

  public function getEventRegistrations($contactId, $language) {
    $registrations = [];

    $languageCode = $this->getLanguagePrefix($language);

    $sql = "
      select
        date_format(e.start_date, '%Y-%m-%d') event_start_date,
        e.id event_id,
        e.title event_title,
        date_format(p.register_date, '%Y-%m-%d') registration_date,
        s.label registration_status
      from
        civicrm_participant p
      inner join
        civicrm_event_$languageCode e on e.id = p.event_id
      inner join
        civicrm_participant_status_type_$languageCode s on p.status_id = s.id
      where
        p.contact_id = %1
      order by
        e.start_date desc
      limit
        0,100
    ";
    $sqlParams = [
      1 => [$contactId, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);

    while ($dao->fetch()) {
      $registrations[] = [
        'event_start_date' => $dao->event_start_date,
        'event_id' => $dao->event_id,
        'event_title' => $dao->event_title,
        'registration_date' => $dao->registration_date,
        'registration_status' => $dao->registration_status,
      ];
    }

    return $registrations;
  }

  public function hasEventRegistration($contactId, $eventId) {
    $sql = "
      select
        p.id
      from
        civicrm_participant p
      inner join
        civicrm_participant_status_type s on p.status_id = s.id
      where
        p.contact_id = %1
      and
        p.event_id = %2
      and
        s.is_counted = 1
    ";
    $sqlParams = [
      1 => [$contactId, 'Integer'],
      2 => [$eventId, 'Integer'],
    ];
    $participantId = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);

    if ($participantId) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function getParticipantId($eventId, $contactId) {
    $participant = civicrm_api3('Participant', 'getsingle', [
      'contact_id' => $contactId,
      'event_id' => $eventId,
    ]);

    return $participant['id'];
  }

  private function setRegisterBy($mainContactParticipantId, $participantId) {
    $sql = "update civicrm_participant set registered_by_id = $mainContactParticipantId where id = $participantId";
    CRM_Core_DAO::executeQuery($sql);
  }

  private function registerAsTrainingResponsible($eventId, $contactId) {
    $participant = civicrm_api3('Participant', 'create', [
      'contact_id' => $contactId,
      'event_id' => $eventId,
      'status_id' => 1, // registered
      'role_id' => [5], // event responsible
    ]);

    return $participant['id'];
  }

  private function getContactId($participant) {
    $params = [
      'sequential' => 1,
      'first_name' => $participant->first_name,
      'last_name' => $participant->last_name,
      'email' => $participant->email,
      'contact_type' => 'Individual',
      'location_type_id' => 3,
    ];
    $contact = civicrm_api3('Contact', 'getorcreate', $params);
    return $contact['id'];
  }

  private function isRegistered($contactId, $eventId) {
    $sql = "select id from civicrm_participant where contact_id = $contactId and event_id = $eventId";
    $participantId = CRM_Core_DAO::singleValueQuery($sql);
    if ($participantId) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function saveEventRegistration($orderHeader, $product, $contactId, $eventId) {
    $params = [
      'sequential' => 1,
      'registration_date' => $orderHeader['order_date'],
      'contact_id' => $contactId,
      'event_id' => $eventId,
      'source' => 'OrderId:' . $orderHeader['order_id'],
      'fee_amount' => $product->total_amount,
      'status_id' => 1, // registered
      'role_id' => 1,
    ];

    $participant = civicrm_api3('Participant', 'create', $params);

    return $participant['values'][0]['id'];
  }

  private function saveEventPayment($orderHeader, $product, $contactId, $eventId, $participantId) {
    $contrib = new CRM_Websiteapi_Contribution();
    $contribId = $contrib->createParticipantPayment($orderHeader, $product, $contactId, $participantId);

    $this->linkContributionToParticipant($contribId, $participantId);
  }

  private function linkContributionToParticipant($contributionId, $participantId) {
    $params = [
      'participant_id' => $participantId,
      'contribution_id' => $contributionId,
    ];
    civicrm_api3('ParticipantPayment', 'create', $params);
  }

  private function saveRegistrationNotes($participantId, $notes) {
    $params = [
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participantId,
      'note' => $notes,
    ];
    civicrm_api3('Note', 'create', $params);
  }

  private function getLanguagePrefix($language) {
    $lowercaseLang = strtolower($language);

    switch ($lowercaseLang) {
      case 'en_us':
      case 'en':
        return 'en_US';
      case 'fr_fr':
      case 'fr':
        return 'fr_FR';
      default:
        return 'nl_NL';
    }
  }
}
