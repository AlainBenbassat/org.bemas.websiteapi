<?php

class CRM_Websiteapi_Member {
  private const RELTYPE_PRIMAMARY_MEMBER_CONTACT = 14;
  private const RELTYPE_MEMBER_CONTACT = 15;

  public function getMembers() {
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d');

    $sql = "
      select
        c.id,
        c.organization_name,
        a.street_address,
        a.postal_code,
        a.city,
        ctry.iso_code country_code,
        w.url,
        p.phone,
        e.email,
        actov.label nace_activity,
        act.activity__nl__3 activity_nl,
        act.activity__fr__5 activity_fr,
        act.activity__en__4 activity_en
      from
        civicrm_contact c
      inner join
        civicrm_membership m on m.contact_id = c.id
      left outer join
        civicrm_address a on a.contact_id = c.id and a.is_primary = 1
      left outer join
        civicrm_country ctry on ctry.id = a.country_id
      left outer join
        civicrm_value_activity_9 act on act.entity_id = c.id
      left outer join
        civicrm_website w on w.contact_id = c.id and w.website_type_id = 6
      left outer join
        civicrm_email e on e.contact_id = c.id and e.is_primary = 1
      left outer join
        civicrm_phone p on p.contact_id = c.id and p.is_primary = 1
      left outer join
        civicrm_option_value actov on actov.value = act.type_of_activity__nace__6 and actov.option_group_id = 85
      where
        c.is_deleted = 0
      and
        c.contact_type = 'Organization'
      and
        (m.status_id = 2 or (
          m.start_date <= %1
        and
          m.end_date >= %2
        ))
      and
        m.owner_membership_id is null
      order by
        c.sort_name
    ";
    $sqlParams = [
      1 => [$startDate, 'String'],
      2 => [$endDate, 'String'],
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
    $arr = $dao->fetchAll();

    for ($i = 0; $i < count($arr); $i++) {
      $arr[$i]['primary_member_contacts'] = $this->getMemberContacts($arr[$i]['id'], self::RELTYPE_PRIMAMARY_MEMBER_CONTACT);
      $arr[$i]['member_contacts'] = $this->getMemberContacts($arr[$i]['id'], self::RELTYPE_MEMBER_CONTACT);
    }

    return $arr;
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

  public function getMembership(int $contactId): array {
    $returnArr = [];

    $membership = \Civi\Api4\Membership::get(FALSE)
      ->addSelect('*', 'status_id:label', 'membership_type_id:label')
      ->addWhere('contact_id', '=', $contactId)
      ->addOrderBy('end_date', 'DESC')
      ->execute()
      ->first();

    if ($membership) {
      $returnArr = [
        'membership_type' => $membership['membership_type_id:label'],
        'membership_status' => $membership['status_id:label'],
        'member_since' => $membership['join_date'],
        'end_date' => $membership['end_date'],
        'primary_member_contacts' => $this->getMemberContacts($contactId, self::RELTYPE_PRIMAMARY_MEMBER_CONTACT),
        'member_contacts' => $this->getMemberContacts($contactId, self::RELTYPE_MEMBER_CONTACT),
      ];
    }

    return $returnArr;
  }

  private function getMemberContacts(int $contactId, int $relType): array {
    $returnArray = [];

    $contacts = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('id', 'first_name', 'last_name', 'job_title')
      ->addJoin('Relationship AS relationship', 'INNER', ['relationship.relationship_type_id', '=', $relType], ['id', '=', 'relationship.contact_id_a'])
      ->addWhere('relationship.contact_id_b', '=', $contactId)
      ->addWhere('is_deleted', '=', FALSE)
      ->addWhere('relationship.is_active', '=', TRUE)
      ->addOrderBy('sort_name', 'ASC')
      ->execute();
    foreach ($contacts as $contact) {
      $returnArray[] = [
        'contact_id' => $contact['id'],
        'first_name' => $contact['first_name'],
        'last_name' => $contact['last_name'],
        'job_title' => $contact['job_title'],
      ];
    }

    return $returnArray;
  }

}
