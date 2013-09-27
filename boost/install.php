<?php

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function always_install(&$content)
{
    return true;
    
    Database::phpwsDSNLoader(PHPWS_DSN);
    $db = Database::newDB();
    $db->begin();

    try {

    } catch (\Exception $e) {
        $db->rollback();
        throw $e;
    }
    $db->commit();

    $content[] = 'Tables created';
    return true;
}

?>
