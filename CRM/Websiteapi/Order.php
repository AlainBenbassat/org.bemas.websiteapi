<?php

class CRM_Websiteapi_Order {
  public function createOrder($apiParams) {
    $this->validateContact($apiParams['contact_id']);
    $this->validateOrderStatus($apiParams['order_status']);
    $this->validateTotalAmount($apiParams['total_amount']);

    $products = $this->decodeProducts($apiParams['products']);
    foreach ($products as $product) {
      $this->saveProduct($apiParams['contact_id'], $apiParams['order_date'], $apiParams['order_status'], $product);
    }
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

  private function decodeProducts($jsonProducts) {
    $decodedProducts = json_decode($jsonProducts);

    $this->validateProducts($decodedProducts);

    return $decodedProducts;
  }

  private function validateProducts($decodedProducts) {
    if ($decodedProducts == null) {
      throw new Exception('Cannot decode products.');
    }

    if (!is_array($decodedProducts)) {
      throw new Exception('Products should be an array.');
    }
  }

  private function validateProduct($product) {
    $this->validateProductType($product);
    $this->validateProductId($product);
    $this->validateProductPrice($product);
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

  private function saveProduct($contactId, $orderDate, $orderStatus, $product) {
    $this->validateProduct($product);
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
}
