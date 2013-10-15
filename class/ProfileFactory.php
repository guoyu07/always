<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ProfileFactory {

    /**
     *
     * @return \always\Profile
     */
    public static function getCurrentUserProfile($approved = true)
    {
        $user_id = \Current_User::getId();
        $profile = self::getProfileByUserId($user_id, $approved);
        return $profile;
    }

    public static function getProfilesByParentId($parent_id, $approved = true)
    {
        $db = \Database::newDB();
        $t1 = $db->addTable('always_profile', 't1');
        $t2 = $db->buildTable('always_profile', 't2');
        $c1 = $t1->getFieldConditional('original_id',
                $t2->getField('original_id'));
        $c2 = $t1->getFieldConditional('version', $t2->getField('version'), '<');
        $db->joinResources($t1, $t2, new \Database\Conditional($c1, $c2, 'and'),
                'left outer');
        $t1->addFieldConditional('parent_id', $parent_id);
        $result = $db->select();
        if (empty($result)) {
            return null;
        }

        foreach ($result as $row) {
            $profile = new \always\Profile;
            $profile->setVars($row);
            $listing[$profile->getId()] = $profile;
        }
        return $listing;
    }

    public static function getProfileByOriginalId($original_id, $approved = false)
    {
        $db = \Database::newDB();
        $t1 = $db->addTable('always_profile', 't1');
        $t2 = $db->buildTable('always_profile', 't2');
        $c1 = $t1->getFieldConditional('original_id',
                $t2->getField('original_id'));
        $c2 = $t1->getFieldConditional('version', $t2->getField('version'), '<');
        $db->joinResources($t1, $t2, new \Database\Conditional($c1, $c2, 'and'),
                'left outer');
        $t1->addFieldConditional('original_id', $original_id);

        if ($approved) {
            $t1->addFieldConditional('approved', 1);
        }
        $result = $db->selectOneRow();
        if (empty($result)) {
            return null;
        }
        $profile = new \always\Profile;
        $profile->setVars($result);
        return $profile;
    }

    /**
     * Gets the highest version of the current User's student profile
     *
     * @param integer $user_id
     * @param boolean $approved If true, return only an approved profile
     * @return array
     */
    public static function getProfilesByUserId($user_id, $approved = true)
    {
        $db = \Database::newDB();
        $prot = $db->addTable('always_profile');
        $part = $db->addTable('always_parents', null, false);

        $prot->addOrderBy($prot->getField('version'), 'desc');

        $db->addConditional($part->getFieldConditional('user_id', $user_id));
        $db->addConditional($db->createConditional($part->getField('id'),
                        $prot->getField('parent_id')));
        if ($approved) {
            $db->addConditional($prot->getFieldConditional('approved', 1));
        }
        $result = $db->select();

        if (!empty($result)) {
            $profile = new Profile;
            foreach ($result as $row) {
                $profile->setVars($row);
                $profile_list[$profile->getId()] = $profile;
            }
            return $profile_list;
        } else {
            return null;
        }
    }

    /**
     *
     * @param integer $id
     * @return \always\Profile
     */
    public static function getProfileById($id)
    {
        $profile = new Profile;
        \ResourceFactory::loadById($profile, $id);
        return $profile;
    }

    public static function getProfileByName($name)
    {
        $db = \Database::newDB();
        $prot = $db->addTable('always_profile');
        $prot->addFieldConditional('pname', $name);
        $values = $db->selectOneRow();
        $profile = new \always\Profile;
        if (!empty($values)) {
            $profile->setvars($values);
        }
        return $profile;
    }

    public static function displayProfile(Profile $profile)
    {
        $data = $profile->getStringVars();
        $data['full_name'] = $profile->getFullName();

        $parent = ParentFactory::getParentById($profile->getParentId());
        if ($parent->getUserId() == \Current_User::getId()) {
            $data['parent'] = true;
            $data['button'] = 'Update ' . $profile->getFullName();
        }

        /*
          if (!empty($data['profile_pic'])) {
          $data['profile_pic'] = '<img src="' . $data['profile_pic'] . '" />';
          }
         */

        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Display.html');
        return $template;
    }

    public static function editCurrentUserProfile()
    {
        return self::editProfile(self::getCurrentUserProfile(false),
                        ParentFactory::getCurrentParent());
    }

    public static function editProfile(Profile $profile, Parents $parent)
    {
        javascript('jquery');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/ckeditor/ckeditor.js'></script>");
        $form = $profile->pullForm();
        $form->setEnctype(\Form::enctype_multipart);
        $form->appendCSS('bootstrap');

        if (\Current_User::allow('always')) {

        }
        $form->addSubmit('save_profile', 'Save my profile');
        $form->addSubmit('submit_profile',
                'Save profile and submit for publication');
        $data = $form->getInputStringArray();
        if ($profile->isSaved()) {
            $data['title'] = 'Profile for ' . $profile->getFullName();
        } else {
            $data['title'] = 'Create a new profile';
        }
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Profile/Edit.html');
        return $template;
    }

    private static function directorizeUsername($user_name)
    {
        return 'images/always/' . preg_replace('@\W@', '-',
                        str_replace('@', '-at-', $user_name)) . '/';
    }

    public static function saveProfileImage($file, $parent, $profile)
    {
        extract($file);

        $file_name = preg_replace('/\s/', '-', $name);

        if ($error > 0) {
            throw new always\UserException('Uploaded file caused an error');
        }
        $user_name = $parent->getUsername();

        $parent_directory = self::directorizeUsername($user_name);
        if (!is_dir($parent_directory)) {
            mkdir($parent_directory);
        }

        $full_path = $parent_directory . $file_name;

        move_uploaded_file($tmp_name, $full_path);
        return $full_path;
    }

    public static function saveProfile(\always\Profile $profile)
    {
        $profile->loadPname();
        if (!$profile->isSaved()) {
            $save_original = true;
        }
        \ResourceFactory::saveResource($profile);
        if ($save_original) {
            $profile->copyOriginalId();
            \ResourceFactory::saveResource($profile);
        }
    }

}

?>
