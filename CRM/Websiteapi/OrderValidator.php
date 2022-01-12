<?php

class CRM_Websiteapi_OrderValidator {
  public function validateOrderHeader($apiParams) {
    $this->validateContact($apiParams['contact_id']);
    $this->validateOrderStatus($apiParams['order_status']);
    $this->validateTotalAmount($apiParams['total_amount']);
  }

  public function validateProducts($decodedProducts) {
    if ($decodedProducts == null) {
      throw new Exception('Cannot decode products.');
    }

    if (!is_array($decodedProducts)) {
      throw new Exception('Products should be an array.');
    }
  }

  public function validateProduct($product) {
    $this->validateProductType($product);
    $this->validateProductId($product);
    $this->validateProductPrice($product);
  }

  public function validateEventId($eventId, $orderDate) {
    $event = $this->getEventById($eventId);

    $this->validateEventExists($eventId, $event);
    $this->validateEventRegistrationDate($orderDate, $event);
  }

  public function validateParticipants($decodedParticipants) {
    if ($decodedParticipants == null) {
      throw new Exception('Cannot decode participants.');
    }

    if (!is_array($decodedParticipants)) {
      throw new Exception('Participants should be an array.');
    }
  }

  public function validateParticipant($participant) {
    $this->validateParticipantEmail($participant);
    $this->validateParticipantFirstName($participant);
    $this->validateParticipantLastName($participant);
  }

  private function validateContact($contactId) {
    $contact = $this->getContactById($contactId);

    $this->validateContactExists($contactId, $contact);
    $this->validateContactIsNotDeleted($contactId, $contact);
    $this->validateContactIsIndividual($contactId, $contact);
  }

  private function validateContactExists($contactId, $contact) {
    if ($contact === FALSE) {
      throw new Exception('Contact with id = ' . $contactId . ' not found');
    }
  }

  private function validateContactIsNotDeleted($contactId, $contact) {
    if ($contact['is_deleted'] == 1) {
      throw new Exception('Contact with id = ' . $contactId . ' is deleted');
    }
  }

  private function validateContactIsIndividual($contactId, $contact) {
    if ($contact['contact_type'] != 'Individual') {
      throw new Exception('Contact with id = ' . $contactId . ' is not an individual');
    }
  }

  private function validateProductType($product) {
    if (empty($product->product_type)) {
      throw new Exception('Product should have a field product_type');
    }

    $validProductTypes = ['book', 'event'];
    if (!in_array($product->product_type, $validProductTypes)) {
      throw new Exception('Product type should be: ' . implode(', ', $validProductTypes));
    }
  }

  private function validateProductId($product) {
    if (empty($product->product_id)) {
      throw new Exception('Product should have a field product_id');
    }

    if (!is_numeric($product->product_id)) {
      throw new Exception('product_id should be numeric');
    }
  }

  private function validateProductPrice($product) {
    if (empty($product->product_price)) {
      throw new Exception('Product should have a field product_price');
    }

    if (!is_numeric($product->product_price)) {
      throw new Exception('product_price should be numeric');
    }
  }

  private function validateParticipantEmail($participant) {
    if (empty($participant->email)) {
      throw new Exception('Participant should have a field email');
    }
  }

  private function validateParticipantFirstName($participant) {
    if (empty($participant->first_name)) {
      throw new Exception('Participant should have a field first_name');
    }
  }

  private function validateParticipantLastName($participant) {
    if (empty($participant->last_name)) {
      throw new Exception('Participant should have a field last_name');
    }
  }

  private function validateOrderStatus($orderStatus) {
    if ($orderStatus == 'paid' || $orderStatus == 'pay_later') {
      // OK
    }
    else {
      throw new Exception('Invalid order status. Should be: paid|pay_later');
    }
  }

  private function validateTotalAmount($totalAmount) {
    if (!is_numeric($totalAmount)) {
      throw new Exception('Invalid total amount');
    }
  }

  private function validateEventExists($eventId, $event) {
    if ($event === FALSE) {
      throw new Exception("Cannot find event $eventId");
    }
  }

  private function validateEventRegistrationDate($orderDate, $event) {
    if ($orderDate < $event->registration_start_date || $orderDate > $event->registration_end_date) {
      throw new Exception("Order date is outside event registration date");
    }
  }

  private function getEventById($eventId) {
    $sql = "
      select
        id,
        ifnull(registration_start_date, '2000-01-01') registration_start_date,
        ifnull(registration_end_date, start_date) registration_end_date
      from
        civicrm_event
      where
        id = $eventId
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {
      return $dao;
    }
    else {
      return FALSE;
    }
  }

  private function getContactById($contactId) {
    $result = civicrm_api3('Contact', 'get', ['id' => $contactId, 'sequential' => 1]);
    if ($result['count'] > 0) {
      return $result['values'][0];
    }
    else {
      return FALSE;
    }
  }

}
