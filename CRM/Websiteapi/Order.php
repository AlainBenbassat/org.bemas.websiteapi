<?php

class CRM_Websiteapi_Order {
  private $orderValidator;

  public function __construct() {
    $this->orderValidator = new CRM_Websiteapi_OrderValidator();
  }

  public function createOrder($apiParams) {
    $this->orderValidator->validateOrderHeader($apiParams);

    $orderHeader = $this->getOrderHeader($apiParams);
    $products = $this->decodeProducts($apiParams['products']);
    foreach ($products as $product) {
      $this->saveProduct($orderHeader, $product);
    }
  }

  private function getOrderHeader($apiParams) {
    $fields = ['order_id', 'contact_id', 'order_date', 'order_status'];
    $orderHeader = [];
    foreach ($fields as $field) {
      $orderHeader[$field] = $apiParams->$field;
    }

    return $orderHeader;
  }

  private function decodeProducts($jsonProducts) {
    $decodedProducts = json_decode($jsonProducts);

    $this->orderValidator->validateProducts($decodedProducts);

    return $decodedProducts;
  }

  private function saveProduct($orderHeader, $product) {
    $this->orderValidator->validateProduct($product);

    if ($this->isProductBook($product)) {
      $contrib = new CRM_Websiteapi_Contribution();
      $contrib->createBookPurchase($orderHeader, $product);
    }
    elseif ($this->isProductEvent($product)) {
      $this->orderValidator->validateEventId($product->product_id, $orderHeader['order_date']);
      $participants = $this->decodeParticipants($product->participants);
      foreach ($participants as $participant) {
        $this->orderValidator->validateParticipant($participant);

        $part = new CRM_Websiteapi_Participant();
        $part->createEventRegistration($orderHeader, $product, $participant);
      }
    }
  }

  private function isProductBook($product) {
    if ($product->product_type == 'book') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function isProductEvent($product) {
    if ($product->product_type == 'event') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function decodeParticipants($jsonParticipants) {
    $decodedParticipants = json_decode($jsonParticipants);

    $this->orderValidator->validateParticipants($decodedParticipants);

    return $decodedParticipants;
  }

}
