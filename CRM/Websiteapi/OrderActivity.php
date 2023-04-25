<?php

class CRM_Websiteapi_OrderActivity {
  private const ACTIVITY_TYPE_ID_ORDER = 61;
  private const CUSTOM_FIELD_ID_HAS_COUPON = 177;
  private const CUSTOM_FIELD_ID_COUPONS = 178;
  private const CUSTOM_FIELD_ID_TOTAL_WITHOUT_DISCOUNT = 179;
  private const CUSTOM_FIELD_ID_TOTAL_WITH_DISCOUNT = 180;
  private const CUSTOM_FIELD_ID_ORDER_ID = 185;

  public function create($orderHeader) {
    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $orderHeader['contact_id'],
      'activity_type_id' => self::ACTIVITY_TYPE_ID_ORDER,
      'subject' => CRM_Websiteapi_Order::getOrderUrl($orderHeader['order_id']),
      'status_id' => 'Completed',
      'custom_' . self::CUSTOM_FIELD_ID_HAS_COUPON => (count($orderHeader['coupons']) > 0 ? 1 : 0),
      'custom_' . self::CUSTOM_FIELD_ID_COUPONS => CRM_Websiteapi_Coupon::convertCouponListToString($orderHeader['coupons']),
      'custom_' . self::CUSTOM_FIELD_ID_TOTAL_WITH_DISCOUNT => $orderHeader['total_amount'],
      'custom_' . self::CUSTOM_FIELD_ID_TOTAL_WITHOUT_DISCOUNT => $orderHeader['order_items_amount'],
      'custom_' . self::CUSTOM_FIELD_ID_ORDER_ID => $orderHeader['order_id'],
    ]);
  }

}
