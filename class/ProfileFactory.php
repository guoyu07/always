<?php
namespace always;
/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ProfileFactory {
    public static function getCurrentProfile()
    {
        $user_id = \Current_User::getId();

        $profile = new Profile;

        $db = \Database::newDB();
        $prot = $db->addTable('always_profile');
        $part = $db->addTable('always_parents', null, false);

        $prot->addOrderBy($prot->getField('version'), 'desc');

        $db->addConditional($part->getFieldConditional('user_id', $user_id));
        $db->addConditional($db->createConditional($part->getField('id'), $prot->getField('parent_id')));
        $db->addConditional($prot->getFieldConditional('approved', 1));
        $db->setLimit(1);
        $db->selectInto($profile);
        return $profile;
    }
}

?>
