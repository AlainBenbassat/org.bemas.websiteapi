<?php

class CRM_Websiteapi_Contribution {
  private const CONTRIBUTION_COMPLETED = 1;
  private const CONTRIBUTION_PENDING = 2;
  private const CONTRIBUTION_FINANCIAL_TYPE_EVENT = 4;
  private const CONTRIBUTION_FINANCIAL_TYPE_PRODUCT = 5;

  public function createDefaultPurchase($orderHeader, $product) {
    $params = [
      'source' => 'OrderID:' . $orderHeader['order_id'],
      'financial_type_id' => self::CONTRIBUTION_FINANCIAL_TYPE_PRODUCT,
      'contact_id' => $orderHeader['contact_id'],
      'receive_date' => $orderHeader['order_date'],
      'total_amount' => $product->total_amount,
      'is_pay_later' => $this->getIsPayLaterFromOrderStatus($orderHeader['order_status']),
      'contribution_status_id' => $this->getContributionStatusFromOrderStatus($orderHeader['order_status']),
      'payment_instrument' => 'EFT',
      'sequential' => 1,
    ];
    $contrib = civicrm_api3('Contribution', 'create', $params);

    $params = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contrib['values'][0]['id'],
      'contact_id' => $orderHeader['contact_id'],
      'note' => $product->quantity . 'x ' . $product->product_title,
    ];
    $note = civicrm_api3('Note', 'create', $params);

    return $contrib['values'][0]['id'];
  }

  public function createParticipantPayment($orderHeader, $product, $contactId, $participantId) {
    $params = [
      'source' => 'OrderID:' . $orderHeader['order_id'],
      'financial_type_id' => self::CONTRIBUTION_FINANCIAL_TYPE_EVENT,
      'contact_id' => $contactId,
      'receive_date' => $orderHeader['order_date'],
      'total_amount' => $product->total_amount,
      'is_pay_later' => $this->getIsPayLaterFromOrderStatus($orderHeader['order_status']),
      'contribution_status_id' => $this->getContributionStatusFromOrderStatus($orderHeader['order_status']),
      'payment_instrument' => 'EFT',
      'sequential' => 1,
    ];
    $contrib = civicrm_api3('Contribution', 'create', $params);

    return $contrib['values'][0]['id'];
  }

  private function getIsPayLaterFromOrderStatus($orderStatus) {
    if ($orderStatus == 'paid') {
      return 0;
    }
    else {
      return 1;
    }
  }

  private function getContributionStatusFromOrderStatus($orderStatus) {
    if ($orderStatus == 'paid') {
      return self::CONTRIBUTION_COMPLETED;
    }
    else {
      return self::CONTRIBUTION_PENDING;
    }
  }
}
