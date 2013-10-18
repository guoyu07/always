<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ProfileFactory {

    public static function getProfilesByParentId($parent_id, $approved = true)
    {
        extract(self::getLastVersionDB());
        $t1->addFieldConditional('parent_id', $parent_id);
        if ($approved) {
            $t1->addFieldConditional('approved', 1);
        }
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

    /**
     * Returns an array containing
     * $db = Database\DB object
     * $t1 = Database\Table object using always profile
     * @return array
     */
    private static function getLastVersionDB()
    {
        $db = \Database::newDB();
        $t1 = $db->addTable('always_profile', 't1');
        $t2 = $db->buildTable('always_profile', 't2');
        $c1 = $t1->getFieldConditional('original_id',
                $t2->getField('original_id'));
        $c2 = $t1->getFieldConditional('version', $t2->getField('version'), '<');
        $db->joinResources($t1, $t2, $db->createConditional($c1, $c2, 'and'),
                'left outer');
        return array('db' => $db, 't1' => $t1);
    }

    public static function getCurrentUserProfiles()
    {
        extract(self::getLastVersionDB());
        $t3 = $db->addTable('always_parents', null, false);
        $t1->addFieldConditional('parent_id', $t3->getField('id'));
        $t3->addFieldConditional('user_id', \Current_User::getId());
        $result = $db->select();
        if (!empty($result)) {
            foreach ($result as $row) {
                $profile = new \always\Profile;
                $profile->setVars($row);
                $prows[] = $profile;
            }
            return $prows;
        } else {
            throw new \Exception('No profiles found');
        }
    }

    /**
     * Returns an unpublished profile. If none is found, a new one is created
     * from the preceding.
     *
     * @param integer $original_id
     */
    public static function getUnpublishedProfile($original_id)
    {
        extract(self::getLastVersionDB());
        $t1->addFieldConditional('original_id', $original_id);
        $t1->addFieldConditional('approved', 0);
        $result = $db->selectOneRow();
        if (empty($result)) {
            $source_profile = self::getProfileByOriginalId($original_id, true);
            if (empty($source_profile)) {
                throw new \Exception('Failed to create a cloned profile for parent');
            }
            $source_profile->cloneProfile();
            ProfileFactory::save($source_profile);
            return $source_profile;
        } else {
            $profile = new \always\Profile;
            $profile->setVars($result);
            return $profile;
        }
    }

    public static function post(\Request $request, \always\Profile $profile, \always\Parents $parent)
    {
        $profile->setFirstName($request->getVar('first_name'));
        $profile->setLastName($request->getVar('last_name'));
        $profile->setClassDate($request->getVar('class_date'));
        $profile->setSummary($request->getVar('summary'));
        $profile->setStory($request->getVar('story'));
        $profile->setLastEditor(\Current_User::getDisplayName());
        $profile->stampLastUpdated();
        $profile->setParentId($parent->getId());

        /**
         * This is stub code until multiple images are allowed
         */
        if ($request->isUploadedFile('profile_pic')) {
            $pic = $profile->getProfilePic();

            // deleting old picture for new picture
            if ($pic) {
                $directory = $pic->getDirectory();
                $directory->unlink();
            }

            $file = $request->getUploadedFileArray('profile_pic');
            $image_path = \always\ProfileFactory::saveImage($file, $parent);
            $profile->setProfilePic($image_path);
        }
    }

    public static function getProfileByOriginalId($original_id, $approved = false)
    {
        extract(self::getLastVersionDB());
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

    /**
     * Gets the highest approved version
     * @param string $name
     * @return \always\Profile
     */
    public static function getProfileByName($name)
    {
        extract(self::getLastVersionDB());
        $t1->addFieldConditional('pname', $name);
        $t1->addFieldConditional('approved', 1);
        $result = $db->selectOneRow();
        if (empty($result)) {
            return null;
        }
        $profile = new \always\Profile;
        $profile->setVars($result);
        return $profile;
    }

    public static function display(Profile $profile)
    {
        $data = $profile->getStringVars();
        $data['full_name'] = $profile->getFullName();

        $parent = ParentFactory::getParentById($profile->getParentId());
        if ($parent->getUserId() == \Current_User::getId()) {
            $data['parent'] = true;
            $data['original_id'] = $profile->getOriginalId();
        }
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Display.html');
        return $template;
    }

    public static function update(Profile $profile)
    {
        javascript('jquery');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/ckeditor/ckeditor.js'></script>");
        $form = $profile->pullForm();
        $form->addHidden('profile_id', $profile->getId());
        $form->setEnctype(\Form::enctype_multipart);
        $form->appendCSS('bootstrap');

        if (\Current_User::allow('always')) {
            if (!$profile->isSubmitted()) {
                if (!$profile->isFirst()) {
                    throw new \Exception('Admin should not be allowed to edit unsubmitted profile');
                } else {
                    $form->addSubmit('save_unpublished', 'Save unpublished');
                    $form->addSubmit('save_published', 'Save and Publish');
                }
            } else {
                // profile is submitted
                $form->addSubmit('save_published', 'Approve and save');
            }
        } else {
            $form->addSubmit('save_unpublished', 'Save but do not publish');
            $form->addSubmit('save_published', 'Save and submit for publication');
        }
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

    public static function saveImage($file, $parent)
    {
        $name = $error = $tmp_name = null;
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

    public static function save(\always\Profile $profile)
    {
        $profile->loadPname();
        if (!$profile->isSaved()) {
            $save_original = true;
        }
        \ResourceFactory::saveResource($profile);

        /**
         * The first version will be zero. So if getVersion is false, we copy
         * the id to original id column.
         */
        if (!$profile->getVersion()) {
            $profile->copyOriginalId();
            \ResourceFactory::saveResource($profile);
        }
    }

}

?>
