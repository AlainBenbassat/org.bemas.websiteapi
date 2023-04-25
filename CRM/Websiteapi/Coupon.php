<?php

class CRM_Websiteapi_Coupon {
  public static function convertCouponToString($coupon) {
    // a coupon is an associative array with to keys: admin_name and code
    if (!is_array(($coupon))) {
      return 'Invalid coupon: not an array';
    }

    if (empty($coupon['admin_name'])) {
      return 'Invalid coupon: missing admin_name';
    }

    if (empty($coupon['admin_name'])) {
      return 'Invalid coupon: missing code';
    }

    if ($coupon['admin_name'] == $coupon['code']) {
      return $coupon['admin_name'];
    }

    return $coupon['admin_name'] . ' (' . $coupon['code'] . ')';
  }

  public static function convertCouponListToString($couponList) {
    $couponsAsString = '';

    foreach ($couponList as $coupon) {
      if ($couponsAsString) {
        $couponsAsString .= ', ';
      }

      $couponsAsString .= self::convertCouponToString($coupon);
    }

    return $couponsAsString;
  }
}
