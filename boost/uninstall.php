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
    
    if ($db->tableExists('always_student')) {
        $tbl = $db->buildTable('always_student');
        $tbl->drop();
   }

    return true;
}

?>
