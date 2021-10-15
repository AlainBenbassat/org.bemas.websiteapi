<?php

class CRM_Websiteapi_Member {
  public function getMembers() {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');

    $sql = "
      select
        c.organization_name
      from
        civicrm_contact c
      inner join
        civicrm_membership m on m.contact_id = c.id
      where
        c.is_deleted = 0
      and
        c.contact_type = 'Organization'
      and
        m.start_date <= %1
      and
        m.end_date >= %2
    ";
    $sqlParams = [
      1 => [$startDate, 'String'],
      2 => [$endDate, 'String'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    return $dao->fetchAll();
  }
}
