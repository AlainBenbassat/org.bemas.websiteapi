<?php

class CRM_Websiteapi_OrderActivity {
  private const ACTIVITY_TYPE_ID_ORDER = 61;
  private const CUSTOM_FIELD_ID_HAS_COUPON = 177;
  private const CUSTOM_FIELD_ID_COUPONS = 178;
  private const CUSTOM_FIELD_ID_TOTAL_WITHOUT_DISCOUNT = 179;
  private const CUSTOM_FIELD_ID_TOTAL_WITH_DISCOUNT = 180;

  public function create($orderHeader, $products) {
    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $orderHeader['contact_id'],
      'activity_type_id' => self::ACTIVITY_TYPE_ID_ORDER,
      'subject' => CRM_Websiteapi_Order::getOrderUrl($orderHeader['order_id']),
      'status_id' => 'Completed',
      'custom_' . self::CUSTOM_FIELD_ID_HAS_COUPON => $this->hasCoupons($orderHeader),
      'custom_' . self::CUSTOM_FIELD_ID_COUPONS => $this->listCoupons($orderHeader),
      'custom_' . self::CUSTOM_FIELD_ID_TOTAL_WITH_DISCOUNT => $orderHeader['total_amount'],
      'custom_' . self::CUSTOM_FIELD_ID_TOTAL_WITHOUT_DISCOUNT => $orderHeader['order_items_amount'],
    ]);
  }

  private function hasCoupons($orderHeader) {
    if (count($orderHeader['coupons']) > 0) {
      return 1;
    }
    else {
      return 0;
    }
  }

  private function listCoupons($orderHeader) {
    $couponList = '';

    foreach ($orderHeader['coupons'] as $coupon) {
      if ($couponList) {
        $couponList .= ', ';
      }

      if ($coupon['admin_name'] == $coupon['code']) {
        $couponList .= $coupon['admin_name'];
      }
      else {
        $couponList .= $coupon['admin_name'] . ' (' . $coupon['code'] . ')';
      }
    }

    return $couponList;
  }

}
