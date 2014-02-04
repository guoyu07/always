<?php
namespace always\Factory;
/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ImageFactory {
    public static function getProfileImages(\always\Resource\Profile $profile)
    {
        $db = \Database::newDB();
        $db->addTable('always_images');
    }
}

?>
