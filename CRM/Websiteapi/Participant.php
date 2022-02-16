<?php

class CRM_Websiteapi_Participant {
  public function createEventRegistration($orderHeader, $product, $participant) {
    $contactId = $this->getContactId($participant);
    $eventId = $product->product_id;

    if (!$this->isRegistered($contactId, $eventId)) {
      $participantId = $this->saveEventRegistration($orderHeader, $product, $contactId, $eventId);

      if ($product->total_amount > 0) {
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
}
