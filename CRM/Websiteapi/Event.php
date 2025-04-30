<?php

class CRM_Websiteapi_Event {
  public function getParticipantsCount($eventId) {
    $sql = "
        select
          count(*)
        from
          civicrm_participant p
        inner join
          civicrm_participant_status_type pt on p.status_id = pt.id
        where
          p.event_id = %1
        and
          pt.class = 'Positive'
        and
          p.role_id like '%1%'
      ";
    $sqlParams = [
      1 => [$eventId, 'Integer'],
    ];
    return CRM_Core_DAO::singleValueQuery($sql, $sqlParams);
  }
}
