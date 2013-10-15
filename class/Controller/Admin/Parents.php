<?php

namespace always\Controller\Admin;

require_once PHPWS_SOURCE_DIR . 'mod/always/inc/defines.php';

/**
 * The controller for parent administration.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Parents extends \Http\Controller {

    private $menu;
    private $parent;

    public function __construct(\Module $module)
    {
        parent::__construct($module);
    }

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    private function loadParent($request)
    {
        $parent_id = $request->getVar('parent_id');
        if ($parent_id) {
            $this->parent = \always\ParentFactory::getParentById($parent_id);
        } else {
            $this->parent = new \always\Parents;
        }
    }

    private function saveParent(\Request $request)
    {
        $this->parent->setFirstName($request->getVar('first_name'));
        $this->parent->setLastName($request->getVar('last_name'));
        if (!$this->parent->isSaved()) {
            $new_user_id = $this->createNewUser($request->getVar('username'));
            $this->parent->setUserId($new_user_id);
        }
        \ResourceFactory::saveResource($this->parent);

        $profile = new \always\Profile;
        $profile->setFirstName($request->getVar('student_first_name'));
        $profile->setLastName($request->getVar('student_last_name'));
        $profile->setClassDate($request->getVar('class_date'));
        $profile->setParentId($this->parent->getId());

        \always\ProfileFactory::saveProfile($profile);
    }

    public function post(\Request $request)
    {
        $this->loadParent($request);

        switch ($request->getVar('command')) {
            case 'save':
                $this->saveParent($request);
                break;

            case 'delete':
                $this->deleteParent();
                break;
        }
        $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
        return $response;
    }

    private function createNewUser($username)
    {
        $username = strtolower($username);
        $user = new \PHPWS_User;

        if ($user->isDuplicateUsername($username)) {
            throw new \Exception('User already in system');
        }

        $user->username = $user->email = $username;

        if ($user->isDuplicateEmail()) {
            throw new \Exception('Email address already in system');
        }

        $user->setActive(1);
        $user->setApproved(1);
        $user->save();

        $password = randomString();

        // This is for testing. REMOVE BEFORE PUBLISHING.
        $password = 'password';

        $password_hash = md5($user->username . $password);

        $db = \Database::newDB();
        $t = $db->addTable('user_authorization');
        $t->addValue('username', $username);
        $t->addValue('password', $password_hash);
        $t->insert();

        return $user->id;
    }

    public function getHtmlView($data, \Request $request)
    {
        $this->loadMenu();
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'list';
        }

        switch ($cmd) {
            case 'list':
                $template = $this->listing($request);
                break;

            case 'edit':
                $template = $this->editCurrentProfile($request);
                break;
        }
        $template->add('menu', $this->menu->get());
        return $template;
    }

    private function loadMenu()
    {
        $this->menu = new \always\Menu('parents');
    }

    /**
     * Here is the logic for whether the administrator edits the profile passed
     * by original id.
     *
     * Administrator
     * --------------
     * If unapproved, force approval.
     * If unsubmitted, get the last approved version
     * If approved or this is the first version, use current profile.
     *
     * We allow the first version without getting in the way because the admin
     * may want to enter some initial information. The admin can then pass it
     * on to the parent in an unsubmitted state.
     *
     */
    private function editCurrentProfile(\Request $request)
    {
        $this->loadParent($request);
        $original_id = $request->getVar('original_id');
        $profile = \always\ProfileFactory::getProfileByOriginalId($original_id);

        if (!$profile->isApproved()) {
            if ($profile->isSubmitted()) {
                // profile was submitted and is awaiting approval, force view
                // so admin may approve
                return \always\ProfileFactory::view($profile);
            } elseif (!$profile->isFirst()) {
                // not the first version, not approved and not submitted.
                // Since this is a work in progress from the parent we pull the
                // last approved profile
                $profile = \always\ProfileFactory::getProfileByOriginalId($original_id,
                                true);
            }
        }

        $template = \always\ProfileFactory::editProfile($profile, $this->parent);

        return $template;
    }

    private function listing($request)
    {
        \Pager::prepare();
        javascript('jquery');
        javascript('jquery_ui');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/Parents/script.js'></script>");
        \Layout::addStyle('always', 'style.css');
        $parent = new \always\Parents;
        $form = $parent->pullForm();
        $form->requiredScript();
        $form->appendCSS('bootstrap');
        $form->setAction('/always/admin/parents');
        $form->addHidden('command', 'save');
        $form->addHidden('parent_id', 0);

        if (!$parent->getId()) {
            $form->addEmail('username')->setPlaceholder("Enter parent's email address")->setRequired();
        }

        $form->getSingleInput('first_name')->setRequired();
        $form->getSingleInput('last_name')->setRequired();

        $form->addTextField('student_first_name')->setRequired();
        $form->addTextField('student_last_name')->setRequired();

        $class_date = range(CLASS_DATE_LOW_RANGE, date('Y') + 4, 1);
        $class_date = array_combine($class_date, $class_date);
        $form->addSelect('class_date', $class_date)->setLabel('Student class date');

        $form->addSubmit('submit', 'Save parent');
        $data = $form->getInputStringArray();

        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Admin/Parents/List.html');
        return $template;
    }

    private function pagerData()
    {
        $db = \Database::newDB();
        $parent = $db->addTable('always_parents');
        $users = $db->addTable('users');
        $parent->addField('id');
        $first_name = $parent->addField('first_name');
        $last_name = $parent->addField('last_name');
        $username = $users->addField('username');
        $db->addConditional($db->createConditional($users->getField('id'),
                        $parent->getField('user_id')));
        $db->setGroupBy($last_name);
        $pager = new \DatabasePager($db);
        $pager->setHeaders(array('last_name', 'first_name', 'username'));
        $tbl_headers['last_name'] = $last_name;
        $tbl_headers['first_name'] = $first_name;
        $tbl_headers['username'] = $username;
        $pager->setTableHeaders($tbl_headers);
        $pager->setId('parent-list');
        $pager->setRowIdColumn('id');
        return $pager->getJson();
    }

    protected function getJsonView($data, \Request $request)
    {
        if ($request->isVar('command')) {
            switch ($request->getVar('command')) {
                case 'edit_parent':
                    $data = $this->editParentJson($request);
                    break;

                case 'delete_parent':
                    $data = $this->deleteParentJson($request);
                    break;

                case 'pager':
                    $data = $this->pagerData();
                    break;
            }
        } else {
            throw new \Exception('JSON command not found');
        }
        return parent::getJsonView($data, $request);
    }

    private function deleteParentJson(\Request $request)
    {
        \always\ParentFactory::deleteParentById($request->getVar('parent_id'));
    }

    private function getJSONProfileListing($parent_id)
    {
        $content = array();
        $profiles = \always\ProfileFactory::getProfilesByParentId($parent_id);
        if (empty($profiles)) {
            return 'No profiles found for this parent';
        }

        $content[] = '<ul style="margin-left:4px;padding-left:0px;list-style-type:none">';
        foreach ($profiles as $p) {
            $content[] = '<li><a href="always/admin/parents/edit?parent_id='
                    . $parent_id . '&amp;original_id=' . $p->getOriginalId() . '">' . $p->getFullName() . ' - Class of '
                    . $p->getClassDate() . '</a></li>';
        }
        $content[] = '</ul>';
        return implode("\n", $content);
    }

    private function editParentJson(\Request $request)
    {
        $parent = \always\ParentFactory::getParentById($request->getVar('pid'));
        $data['first_name'] = $parent->getFirstName();
        $data['last_name'] = $parent->getLastName();
        $data['username'] = $parent->getUsername();
        $data['profile_list'] = $this->getJSONProfileListing($parent->getId());
        return $data;
    }

}

?>
