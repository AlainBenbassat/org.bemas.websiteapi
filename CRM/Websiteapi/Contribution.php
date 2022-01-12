<?php

class CRM_Websiteapi_Contribution {
  private const CONTRIBUTION_COMPLETED = 1;
  private const CONTRIBUTION_PENDING = 2;
  private const CONTRIBUTION_FINANCIAL_TYPE_EVENT = 4;
  private const CONTRIBUTION_FINANCIAL_TYPE_PRODUCT = 5;

  public function createBookPurchase($orderHeader, $product) {
    $params = [
      'source' => 'OrderID:' . $orderHeader['order_id'],
      'financial_type_id' => self::CONTRIBUTION_FINANCIAL_TYPE_PRODUCT,
      'contact_id' => $orderHeader['contact_id'],
      'receive_date' => $orderHeader['order_date'],
      'total_amount' => $orderHeader['total_amount'],
      'is_pay_later' => $this->getIsPayLaterFromOrderStatus($orderHeader['order_status']),
      'contribution_status_id' => $this->getContributionStatusFromOrderStatus($orderHeader['order_status']),
      'payment_instrument' => 'EFT',
    ];
    civicrm_api3('Contribution', 'create', $params);
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
