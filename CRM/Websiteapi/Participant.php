<?php

class CRM_Websiteapi_Participant {
  private const CUSTOM_FIELD_ID_DIET = 130;
  private const CUSTOM_FIELD_ID_SHARE_MY_DATA = 168;
  private const CUSTOM_FIELD_ID_EMPLOYER = 186;
  private const CUSTOM_FIELD_ID_JOB_TITLE = 187;
  private const CUSTOM_FIELD_ID_COUPON = 188;

  public function createEventRegistration($participantCounter, $orderHeader, $product, $participant) {
    $contactId = $this->getContactId($participant);
    $this->createOrUpdatePhone($contactId, $participant->telephone);

    $eventId = $product->product_id;

    if (!$this->isRegistered($contactId, $eventId)) {
      [$unitPriceWithDiscount, $discountCode] = $this->calculateParticipantPrice($participantCounter, $orderHeader, $product);

      $participantId = $this->saveEventRegistration($orderHeader, $product, $contactId, $eventId, $participant, $unitPriceWithDiscount, $discountCode);

      if ($unitPriceWithDiscount > 0) {
        $this->saveEventPayment($orderHeader, $product, $contactId, $participantId, $unitPriceWithDiscount, $discountCode);
      }

      if (!empty($participant->notes)) {
        $this->saveEventRegistrationNotes($contactId, $participantId, $participant->notes);
      }
    }
    else {
      $this->reactivateCancelledParticipant($contactId, $eventId);
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
      and
        s.class = 'Positive'
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

  private function calculateParticipantPrice($participantCounter, $orderHeader, $product) {
    // default is: no discount
    $unitPriceWithDiscount = $product->unit_price;
    $discountCode = '';

    // see if we have a discount for this participant
    if (count($product->adjustments) > 0 && isset($product->adjustments[$participantCounter])) {
      $unitPriceWithDiscount = $product->unit_price + $product->adjustments[$participantCounter];
      $discountCode = $orderHeader['coupons'];
    }

    return [$unitPriceWithDiscount, $discountCode];
  }

  private function getParticipantId($eventId, $contactId) {
    try {
      $participant = civicrm_api3('Participant', 'getsingle', [
        'contact_id' => $contactId,
        'event_id' => $eventId,
      ]);

      return $participant['id'];
    }
    catch (Exception $e) {
      return 0;
    }
  }

  private function setRegisterBy($mainContactParticipantId, $participantId) {
    if ($mainContactParticipantId > 0) {
      $sql = "update civicrm_participant set registered_by_id = $mainContactParticipantId where id = $participantId";
      CRM_Core_DAO::executeQuery($sql);
    }
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

  private function reactivateCancelledParticipant($contactId, $eventId) {
    $sql = "
      update
        civicrm_participant
      set
        status_id = 1
      where
        status_id = 4
      and
        contact_id = $contactId
      and
        event_id = $eventId
    ";
    CRM_Core_DAO::singleValueQuery($sql);
  }

  private function saveEventRegistration($orderHeader, $product, $contactId, $eventId, $participant, $unitPriceWithDiscount, $discountCode) {
    $params = [
      'sequential' => 1,
      'registration_date' => $orderHeader['order_date'],
      'contact_id' => $contactId,
      'event_id' => $eventId,
      'source' => CRM_Websiteapi_Order::getOrderUrl($orderHeader['order_id']),
      'fee_amount' => $unitPriceWithDiscount,
      'status_id' => 1, // registered
      'role_id' => 1,
      'custom_' . self::CUSTOM_FIELD_ID_SHARE_MY_DATA => 1,
      'custom_' . self::CUSTOM_FIELD_ID_DIET => $participant->diet,
      'custom_' . self::CUSTOM_FIELD_ID_EMPLOYER => $participant->current_employer,
      'custom_' . self::CUSTOM_FIELD_ID_JOB_TITLE => $participant->function,
      'custom_' . self::CUSTOM_FIELD_ID_COUPON => $discountCode,
    ];

    $participant = civicrm_api3('Participant', 'create', $params);
    $participantId = $participant['values'][0]['id'];

    return $participantId;
  }

  private function saveEventRegistrationNotes($contactId, $participantId, $notes) {
    $params = [
      'entity_table' => 'civicrm_participant',
      'entity_id' => $participantId,
      'note' => $notes,
      'contact_id' => $contactId,
    ];

    civicrm_api3('Note', 'create', $params);
  }

  private function saveEventPayment($orderHeader, $product, $contactId, $participantId, $unitPriceWithDiscount, $discountCode) {
    $contrib = new CRM_Websiteapi_Contribution();
    $contribId = $contrib->createParticipantPayment($orderHeader, $product, $contactId, $unitPriceWithDiscount, $discountCode);

    $this->linkContributionToParticipant($contribId, $participantId);
  }

  private function linkContributionToParticipant($contributionId, $participantId) {
    $params = [
      'participant_id' => $participantId,
      'contribution_id' => $contributionId,
    ];
    civicrm_api3('ParticipantPayment', 'create', $params);
  }

  private function createOrUpdatePhone($contactId, $phone) {
    if ($phone) {
      if ($this->hasRegistrationPhone($contactId)) {
        $this->updateRegistrationPhone($contactId, $phone);
      }
      else {
        $this->createRegistrationPhone($contactId, $phone);
      }
    }
  }

  private function hasRegistrationPhone($contactId) {
    $phoneId = CRM_Core_DAO::singleValueQuery("select id from civicrm_phone where location_type_id = 3 and phone_type_id = 6 and contact_id = $contactId");
    if ($phoneId) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function updateRegistrationPhone($contactId, $phone) {
    $sql = "
      update
        civicrm_phone
      set
        phone = %1
      where
        location_type_id = 3
      and
        phone_type_id = 6
      and
        contact_id = $contactId
    ";
    $sqlParams = [
      1 => [$phone, 'String'],
    ];
    CRM_Core_DAO::executeQuery($sql, $sqlParams);
  }

  private function createRegistrationPhone($contactId, $phone) {
    $params = [
      'sequential' => 1,
      'phone' => $phone,
      'contact_id' => $contactId,
      'location_type_id' => 3,
      'phone_type_id' => 6,
    ];
    $result = civicrm_api3('Phone', 'create', $params);
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
