<?php

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function always_install(&$content)
{
    Database::phpwsDSNLoader(PHPWS_DSN);
    $db = Database::newDB();
    $db->begin();

    try {
        $student = new always\Student;
        $st = $student->createTable($db);

        $profile = new always\Profile;
        $pt = $profile->createTable($db);
    } catch (\Exception $e) {
        if (isset($st) && $db->tableExists($st->getName())) {
            $st->drop();
        }
        if (isset($pt) && $db->tableExists($pt->getName())) {
            $pt->drop();
        }
        $db->rollback();
        throw $e;
    }
    $db->commit();

    $content[] = 'Tables created';
    return true;
}

?>
