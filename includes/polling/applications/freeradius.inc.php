<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!empty($agent_data['app']['freeradius'])) {
    $app_id = discover_app($device, 'freeradius');

    $data = explode("\n", $agent_data['app']['freeradius']);

    $map = [];
    foreach ($data as $str) {
        [$key, $value] = explode(":", $str);
        $map[$key] = (float)trim($value);
    }

    $data = [
      'AccessAccepts'       => $map['FreeRADIUS-Total-Access-Accepts'],
      'AccessChallenges'    => $map['FreeRADIUS-Total-Access-Challenges'],
      'AccessRejects'       => $map['FreeRADIUS-Total-Access-Rejects'],
      'AccessReqs'          => $map['FreeRADIUS-Total-Access-Requests'],
      'AccountingReqs'      => $map['FreeRADIUS-Total-Accounting-Requests'],
      'AccountingResponses' => $map['FreeRADIUS-Total-Accounting-Responses'],
      'AcctDroppedReqs'     => $map['FreeRADIUS-Total-Acct-Dropped-Requests'],
      'AcctDuplicateReqs'   => $map['FreeRADIUS-Total-Acct-Duplicate-Requests'],
      'AcctInvalidReqs'     => $map['FreeRADIUS-Total-Acct-Invalid-Requests'],
      'AcctMalformedReqs'   => $map['FreeRADIUS-Total-Acct-Malformed-Requests'],
      'AcctUnknownTypes'    => $map['FreeRADIUS-Total-Acct-Unknown-Types'],
      'AuthDroppedReqs'     => $map['FreeRADIUS-Total-Auth-Dropped-Requests'],
      'AuthDuplicateReqs'   => $map['FreeRADIUS-Total-Auth-Duplicate-Requests'],
      'AuthInvalidReqs'     => $map['FreeRADIUS-Total-Auth-Invalid-Requests'],
      'AuthMalformedReqs'   => $map['FreeRADIUS-Total-Auth-Malformed-Requests'],
      'AuthResponses'       => $map['FreeRADIUS-Total-Auth-Responses'],
      'AuthUnknownTypes'    => $map['FreeRADIUS-Total-Auth-Unknown-Types'],
    ];

    update_application($app_id, $data);

    rrdtool_update_ng($device, 'freeradius', $data, $app_id);

    unset($map);
}

// EOF
