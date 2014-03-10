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

    if ($db->tableExists('always_parents')) {
        $tbl = $db->buildTable('always_parents');
        $tbl->drop();
    }
    if ($db->tableExists('always_profile')) {
        $tbl = $db->buildTable('always_profile');
        $tbl->drop();
    }
    if ($db->tableExists('always_image')) {
        $tbl = $db->buildTable('always_image');
        $tbl->drop();
    }

    return true;
}

?>
