<?php

namespace always\Factory;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ParentFactory {

    public static function getCurrentParent()
    {
        return self::getParentByUserId(\Current_User::getId());
    }

    public static function getParentById($id)
    {
        $parent = new \always\Resource\Parents;
        if ($id) {
            \ResourceFactory::loadById($parent, $id);
        }

        return $parent;
    }

    public static function getParentByUserId($user_id)
    {
        $parent = new \always\Resource\Parents;
        $db = \Database::newDB();
        $ap = $db->addTable('always_parents');
        $ap->addFieldConditional('user_id', \Current_User::getId());
        $db->selectInto($parent);
        return $parent;
    }

    public static function deleteParentById($id)
    {
        $parent = self::getParentById($id);
        if (!$parent->isSaved()) {
            throw new \Exception("Parent id $id not found");
        }
        $db = \Database::newDB();
        $db->addTable('always_parents')->addFieldConditional('id', $id);
        $db->delete();

        $user = new \PHPWS_User($parent->getUserId());
        $user->kill();

        $db = \Database::newDB();
        $db->addTable('always_profile')->addFieldConditional('parent_id',
                $parent->getId());
        $profiles = $db->select();
        $db->delete();

        if (!empty($profiles)) {
            foreach ($profiles as $pf) {
                $profile = new \always\Resource\Profile;
                $profile->setVars($pf);
                $dir = $profile->getImageDirectory();
                if (is_dir($dir)) {
                    rmdir($dir);
                }
            }
        }
    }

}

?>
