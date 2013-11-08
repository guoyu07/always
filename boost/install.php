<?php

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @package Global
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
function always_install(&$content)
{
    $db = Database::newDB();
    $db->begin();

    try {
        $parent = new \always\Resource\Parents;
        $st = $parent->createTable($db);

        $profile = new \always\Resource\Profile;
        $pt = $profile->createTable($db);

        $index = new \Database\Index($pt->getDataType('pname'), 'pname');
        $index->create();
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
