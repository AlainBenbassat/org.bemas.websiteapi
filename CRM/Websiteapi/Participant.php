<?php

class CRM_Websiteapi_Participant {
  public function createEventRegistration($orderHeader, $product, $participant) {
    $contactId = $this->getContactId($participant);
    $eventId = $product->product_id;

    if (!$this->isRegistered($contactId, $eventId)) {
      $participantId = $this->saveEventRegistration($orderHeader, $product, $contactId, $eventId);

      if ($product->total_amount > 0) {
        $this->saveEventPayment();
      }
    }
  }

  private function getContactId($participant) {
    $params = [
      'sequential' => 1,
      'first_name' => $participant->first_name,
      'last_name' => $participant->last_name,
      'email' => $participant->email,
      'contact_type' => 'Individual',
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
    ];

    $participant = civicrm_api3('Participant', 'create', $params);

    return $participant['id'];
  }
}
