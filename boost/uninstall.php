<?php

/**
 * Uninstall file for blog
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @version $Id$
 */

function always_uninstall(&$content)
{
    $db = Database::newDB();
    $db->buildTable('always_student')->drop();
    return true;
}


?>
