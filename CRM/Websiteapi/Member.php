<?php

class CRM_Websiteapi_Member {
  public function getMembers() {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');

    $sql = "
      select
        c.id,
        c.organization_name,
        a.postal_code,
        a.city
      from
        civicrm_contact c
      inner join
        civicrm_membership m on m.contact_id = c.id
      left outer join
        civicrm_address a on a.contact_id = c.id and a.is_primary = 1
      where
        c.is_deleted = 0
      and
        c.contact_type = 'Organization'
      and
        m.status_id = 2 or (
          m.start_date <= %1
        and
          m.end_date >= %2
        )
      order by
        c.sort_name
    ";
    $sqlParams = [
      1 => [$startDate, 'String'],
      2 => [$endDate, 'String'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    return $dao->fetchAll();
  }

  public function isMember($contactId) {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');

    $sql = "select count(id) from civicrm_membership where (status_id = 2 or (start_date <= %1 and end_date >= %2)) and contact_id = %3";
    $sqlParams = [
      1 => [$startDate, 'String'],
      2 => [$endDate, 'String'],
      3 => [$contactId, 'Integer'],
    ];
    $memberCount = CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
    if ($memberCount) {
      return 1;
    }
    else {
      return 0;
    }
  }

}
