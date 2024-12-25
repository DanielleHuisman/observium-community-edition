<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

if ($_SESSION['userlevel'] > 5 || $auth) {
    $auth = 1;
} else {
    // error?
}

// EOF
