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

    if ($db->tableExists('always_parentss')) {
        $tbl = $db->buildTable('always_parentss');
        $tbl->drop();
   }
    if ($db->tableExists('always_profile')) {
        $tbl = $db->buildTable('always_profile');
        $tbl->drop();
   }

    return true;
}

?>
