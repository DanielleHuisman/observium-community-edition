<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) Adam Armstrong
 *
 */
if ($_SESSION['userlevel'] < '7') {
    print_error("Insufficient Privileges");
} else {
    r($port);
}
// EOF
