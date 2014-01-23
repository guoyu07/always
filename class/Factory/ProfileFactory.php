<?php

namespace always\Factory;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ProfileFactory {

    /**
     *
     * @param type $parent_id
     * @param bool $approved If null, pull all profiles. True, only approved. False, only unapproved
     * @return null|\always\Resource\Profile
     */
    public static function getProfilesByParentId($parent_id, $approved = null)
    {
        $db = $profile_table = null;
        extract(self::getLastVersionDB($approved));
        $profile_table->addFieldConditional('parent_id', $parent_id);
        $result = $db->select();
        if (empty($result)) {
            return null;
        }
        foreach ($result as $row) {
            $profile = new \always\Resource\Profile;
            $profile->setVars($row);
            $listing[$profile->getId()] = $profile;
        }
        return $listing;
    }

    /**
     * Returns an array containing
     * $db = Database\DB object
     * $profile_table = Database\Table object using always_profile
     * @return array
     */
    public static function getLastVersionDB($approved = null)
    {
        $ssdb = \Database::newDB();
        $sstbl = $ssdb->addTable('always_profile');
        $pid = $sstbl->addField('original_id');
        $exp = new \Database\Expression('max(' . $sstbl->getField('version') . ')',
                'newest_version');
        $sstbl->addField($exp);
        if (!is_null($approved)) {
            $sstbl->addFieldConditional('approved', (int) (bool) $approved);
        }
        $ssdb->setGroupBy($pid);

        $subselect = new \Database\SubSelect($ssdb, 'sub');

        $db = \Database::newDB();
        $profile_table = $db->addTable('always_profile');

        $c1 = new \Database\Conditional($db, $subselect->getField('original_id'),
                $profile_table->getField('original_id'), '=');
        $c2 = new \Database\Conditional($db, $exp,
                $profile_table->getField('version'), '=');
        $c3 = new \Database\Conditional($db, $c1, $c2, 'and');
        $db->joinResources($profile_table, $subselect, $c3);
        return array('db' => $db, 'profile_table' => $profile_table);
    }

    public static function getProfiles($approved = null)
    {
        extract(self::getLastVersionDB($approved));
        $profile_table->addOrderBy($profile_table->getField('last_name'));
        return self::buildProfileArray($db);
    }

    private static function buildProfileArray(\Database\DB $db)
    {
        $result = $db->select();
        if (!empty($result)) {
            foreach ($result as $row) {
                $profile = new \always\Resource\Profile;
                $profile->setVars($row);
                $prows[] = $profile;
            }
            return $prows;
        } else {
            return null;
        }
    }

    public static function getCurrentUserProfiles()
    {
        $db = $profile_table = null;
        extract(self::getLastVersionDB());
        $t3 = $db->addTable('always_parents', null, false);
        $profile_table->addFieldConditional('parent_id', $t3->getField('id'));
        $t3->addFieldConditional('user_id', \Current_User::getId());
        return self::buildProfileArray($db);
    }

    /**
     * Returns an unpublished profile. If none is found, a new one is created
     * from the preceding.
     *
     * @param integer $original_id
     */
    public static function getUnpublishedProfile($original_id)
    {
        $db = $profile_table = null;
        extract(self::getLastVersionDB());
        $profile_table->addFieldConditional('original_id', $original_id);
        $profile_table->addFieldConditional('approved', 0);
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
            $profile = new \always\Resource\Profile;
            $profile->setVars($result);
            return $profile;
        }
    }

    public static function post(\Request $request, \always\Resource\Profile $profile, \always\Resource\Parents $parent)
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
            $image_path = \always\Factory\ProfileFactory::saveImage($file,
                            $parent);
            $profile->setProfilePic($image_path);
        }
    }

    public static function getProfileByOriginalId($original_id, $approved = null)
    {
        $db = $profile_table = null;
        extract(self::getLastVersionDB($approved));
        $profile_table->addFieldConditional('original_id', $original_id);
        $result = $db->selectOneRow();
        if (empty($result)) {
            return null;
        }
        $profile = new \always\Resource\Profile;
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
    public static function getProfilesByUserId($user_id, $approved = null)
    {
        $db = $profile_table = null;
        extract(self::getLastVersionDB($approved));
        $t3 = $db->addTable('always_parents', null, false);
        $profile_table->addFieldConditional('parent_id', $t3->getField('id'));
        $t3->addFieldConditional('user_id', $user_id);
        return self::buildProfileArray($db);
    }

    /**
     *
     * @param integer $id
     * @return \always\Resource\Profile
     */
    public static function getProfileById($id)
    {
        $profile = new \always\Resource\Profile;
        \ResourceFactory::loadById($profile, $id);
        return $profile;
    }

    public static function getLastApprovedByName($name)
    {
        $db = \Database::newDB();
        $profile_table = $db->addTable('always_profile');
        $profile_table->addFieldConditional('approved', 1);
        $profile_table->addOrderBy($profile_table->getField('version'), 'desc');

        $profile_table->addFieldConditional('pname', $name);
        $result = $db->selectOneRow();
        if (empty($result)) {
            return null;
        }
        $profile = new \always\Resource\Profile;
        $profile->setVars($result);
        return $profile;
    }

    /**
     * Gets the highest version of profiles by pname. Note that if approved is
     * true and the last version is not approved, nothing will be returned.
     * @param string $name
     * @return \always\Resource\Profile
     */
    public static function getProfileByName($name, $approved = null)
    {
        extract(self::getLastVersionDB($approved));
        $profile_table->addFieldConditional('pname', $name);
        $result = $db->selectOneRow();
        if (empty($result)) {
            return null;
        }
        $profile = new \always\Resource\Profile;
        $profile->setVars($result);
        return $profile;
    }

    public static function diff(\always\Resource\Profile $old_profile, \always\Resource\Profile $new_profile)
    {
        require_once PHPWS_SOURCE_DIR . 'mod/always/inc/HtmlDiff.php';
        $ovars = $old_profile->getStringVars();
        $nvars = $new_profile->getStringVars();

        foreach ($ovars as $key => $value) {
            $key_diff = $key . '_diff';
            if ($nvars[$key] == $value) {
                $compare = $value;
                $tpl[$key_diff] = '<span class="no-changes">No changes</span>';
            } else {
                $tpl[$key_diff] = '<span class="changed">Changed</span>';
                $diff = new \HtmlDiff($value, $nvars[$key]);
                $compare = $diff->build();
            }
            $tpl[$key] = $compare;
        }
        return $tpl;
    }

    public static function display(\always\Resource\Profile $profile)
    {
        javascript('jquery');
        $script = '<script type="text/javascript" src="' . PHPWS_SOURCE_HTTP . 'mod/always/javascript/fancybox/source/jquery.fancybox.js"></script>
            <script type="text/javascript" src="' . PHPWS_SOURCE_HTTP . 'mod/always/javascript/fancybox/load.js"></script>
            <link rel="stylesheet" type="text/css" href="' . PHPWS_SOURCE_HTTP . 'mod/always/javascript/fancybox/source/jquery.fancybox.css?v=2.1.5" media="screen" />';
        \Layout::addJSHeader($script);
        $data = $profile->getStringVars();
        $data['full_name'] = $profile->getFullName();
        $data['approve'] = false;
        $data['publish'] = false;
        $data['admin'] = false;
        $data['parent_update'] = false;
        $data['profile_id'] = $profile->getId();
        $data['original_id'] = $profile->getOriginalId();

        $parent = ParentFactory::getParentById($profile->getParentId());
        if ($parent->getUserId() == \Current_User::getId()) {
            $data['parent_update'] = true;
            $data['admin'] = true;
            if (!$profile->isApproved()) {
                if ($profile->isSubmitted()) {
                    $data['status'] = 'Awaiting approval. You may continue to update it.';
                } else {
                    $data['publish'] = true;
                    $data['status'] = 'Unsubmitted. Click Publish to submit for approval';
                }
            }
        } elseif (\Current_User::allow('always')) {
            $data['admin'] = true;
            if (!$profile->isApproved() && $profile->isSubmitted()) {
                $data['approve'] = true;
            }
        }
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Display.html');
        return $template;
    }

    public static function form(\always\Resource\Profile $profile)
    {
        javascript('jquery');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/ckeditor/ckeditor.js'></script>");
        $form = $profile->pullForm();

        if (\Current_User::allow('always')) {
            $form->setAction('always/admin/profiles/new');
        } else {
            $form->setAction('always/parent/update');
        }

        $form->requiredScript();
        $form->addHidden('profile_id', $profile->getId());
        $form->addHidden('parent_id', $profile->getParentId());
        $form->setEnctype(\Form::enctype_multipart);
        $form->appendCSS('bootstrap');

        $form->getSingleInput('first_name')->setRequired();
        $form->getSingleInput('last_name')->setRequired();

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
                $form->addSubmit('save_published', 'Update');
            }
        } else {
            $form->addSubmit('save_unpublished', 'Save but do not publish');
            $form->addSubmit('save_published', 'Save and submit for publication');
        }
        $data = $form->getInputStringArray();
        if ($profile->isSaved()) {
            $data['title'] = 'Profile for ' . $profile->getFullName();
        } else {
            $data['title'] = 'Create a new profile for ' . ParentFactory::getParentById($profile->getParentId())->getFullName();
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

    public static function save(\always\Resource\Profile $profile)
    {
        $profile->loadPname();
        $profile->setLastEditor(\Current_User::getUsername());
        $profile->stampLastUpdated();

        \ResourceFactory::saveResource($profile);
        /**
         * The first version will be zero. So if getVersion is false, we copy
         * the id to original id column.
         */
        $version = $profile->getVersion();
        if (!$version) {
            $profile->copyOriginalId();
            \ResourceFactory::saveResource($profile);
        }
    }

}

?>
