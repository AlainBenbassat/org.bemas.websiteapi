<?php

class CRM_Websiteapi_Order {
  private $logOrder = TRUE; // set to TRUE to log the order in ConfigAndLog
  private $orderValidator;
  private $orderActivity;

  public function __construct() {
    $this->orderValidator = new CRM_Websiteapi_OrderValidator();
    $this->orderActivity = new CRM_Websiteapi_OrderActivity();
  }

  public function createOrder($apiParams) {
    if ($this->logOrder) {
      Civi::log()->debug(print_r($apiParams, TRUE));
    }

    $this->orderValidator->validateOrderHeader($apiParams);
    $orderHeader = $this->getOrderHeader($apiParams);

    $products = $apiParams['products'];
    $this->orderValidator->validateProducts($products);

    foreach ($products as $product) {
      $this->saveProduct($orderHeader, $product);
    }

    $this->orderActivity->create($orderHeader);
  }

  public static function getOrderUrl($orderId) {
    return "https://www.bemas.org/nl/admin/commerce/orders/$orderId";
  }

  private function getOrderHeader($apiParams) {
    $orderHeader = [];

    $expectedFields = ['order_id', 'contact_id', 'order_date', 'order_status', 'total_amount', 'order_items_amount'];
    foreach ($expectedFields as $field) {
      $orderHeader[$field] = $apiParams[$field];
    }

    if (!$this->hasCoupons($apiParams)) {
      $orderHeader['coupons'] = [];
    }

    return $orderHeader;
  }

  private function hasCoupons($apiParams) {
    if (!empty($apiParams['coupons']) && count($apiParams['coupons']) > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function saveProduct($orderHeader, $product) {
    $this->orderValidator->validateProduct($product);

    if ($this->isProductDefault($product)) {
      $contrib = new CRM_Websiteapi_Contribution();
      $contrib->createDefaultPurchase($orderHeader, $product);
    }
    elseif ($this->isProductEvent($product)) {
      $this->orderValidator->validateEventId($product['product_id'], $orderHeader['order_date']);
      $this->orderValidator->validateParticipants($product['participants']);

      $registeredContactIds = [];
      $participantCounter = 0;
      foreach ($product['participants'] as $participant) {
        $this->orderValidator->validateParticipant($participant);

        $part = new CRM_Websiteapi_Participant();
        $registeredContactIds[] = $part->createEventRegistration($participantCounter, $orderHeader, $product, $participant);

        $participantCounter++;
      }

      // Uncomment the following when we have sorted out how to deal with the training responsible
      //$part->fillRegisteredBy($product['product_id'], $orderHeader['contact_id'], $registeredContactIds);
    }
  }

  private function isProductDefault($product) {
    if ($product['product_type'] == 'default') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  private function isProductEvent($product) {
    if ($product['product_type'] == 'event') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
