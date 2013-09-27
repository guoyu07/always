<?php

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function always_install(&$content)
{
    //require_once PHPWS_SOURCE_DIR . 'mod/always/class/Student';
    Database::phpwsDSNLoader(PHPWS_DSN);
    $db = Database::newDB();
    $db->begin();

    try {
        $college = new always\Student;
        $college->createTable($db);

    } catch (\Exception $e) {
        $db->rollback();
        throw $e;
    }
    $db->commit();

    $content[] = 'Tables created';
    return true;
}

?>
