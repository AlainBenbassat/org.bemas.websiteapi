<?php

class CRM_Websiteapi_Order {
  private $orderValidator;
  private $orderActivity;

  public function __construct() {
    $this->orderValidator = new CRM_Websiteapi_OrderValidator();
    $this->orderActivity = new CRM_Websiteapi_OrderActivity();
  }

  public function createOrder($apiParams) {
    $this->orderValidator->validateOrderHeader($apiParams);

    $orderHeader = $this->getOrderHeader($apiParams);
    $products = $this->decodeProducts($apiParams['products']);
    foreach ($products as $product) {
      $this->saveProduct($orderHeader, $product);
    }

    $this->orderActivity->create($orderHeader, $products);
  }

  private function getOrderHeader($apiParams) {
    $fields = ['order_id', 'contact_id', 'order_date', 'order_status'];
    $orderHeader = [];
    foreach ($fields as $field) {
      $orderHeader[$field] = $apiParams[$field];
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

    if ($this->isProductDefault($product)) {
      $contrib = new CRM_Websiteapi_Contribution();
      $contrib->createDefaultPurchase($orderHeader, $product);
    }
    elseif ($this->isProductEvent($product)) {
      $this->orderValidator->validateEventId($product->product_id, $orderHeader['order_date']);
      $this->orderValidator->validateParticipants($product->participants);

      $registeredContactIds = [];
      foreach ($product->participants as $participant) {
        $this->orderValidator->validateParticipant($participant);

        $part = new CRM_Websiteapi_Participant();
        $registeredContactIds[] = $part->createEventRegistration($orderHeader, $product, $participant);
      }

      $part->fillRegisteredBy($product->product_id, $orderHeader['contact_id'], $registeredContactIds);
    }
  }

  private function isProductDefault($product) {
    if ($product->product_type == 'default') {
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

}
