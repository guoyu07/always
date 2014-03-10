<?php

namespace always\Controller\Admin;

require_once PHPWS_SOURCE_DIR . 'mod/always/inc/defines.php';

/**
 * The controller for parent administration.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ParentController extends \Http\Controller {

    private $menu;
    /**
     * @var \always\Resource\Parents
     */
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
            $this->parent = \always\Factory\ParentFactory::getParentById($parent_id);
        } else {
            $this->parent = new \always\Resource\Parents;
        }
    }

    private function saveParent(\Request $request)
    {
        $this->parent->setFirstName($request->getVar('first_name'));
        $this->parent->setLastName($request->getVar('last_name'));
        if (!$this->parent->isSaved()) {
            $new_user_id = $this->createNewUser($request->getVar('username'), $this->parent->getFullName());
            $this->parent->setUserId($new_user_id);
        }
        \ResourceFactory::saveResource($this->parent);

        $profile = new \always\Resource\Profile;
        $profile->setFirstName($request->getVar('student_first_name'));
        $profile->setMiddleName($request->getVar('student_middle_name'));
        $profile->setLastName($request->getVar('student_last_name'));
        $profile->setClassDate($request->getVar('class_date'));
        $profile->setParentId($this->parent->getId());

        \always\Factory\ProfileFactory::save($profile);
    }

    private function emailParent()
    {
        require_once PHPWS_SOURCE_DIR . 'lib/Swift/lib/swift_required.php';

        $subject = 'Invitation to Always a Mountaineer';
        $from_email = \Settings::get('always', 'contact_email');
        $from_full_name = 'Always website';

        $to_email = $this->parent->getUsername();
        $to_full_name = $this->parent->getFullName();

        $vars['full_name'] = $this->parent->getFullName();
        $vars['username'] = $this->parent->getUsername();
        $vars['password'] = $this->parent->getPassword();
        $vars['site_address'] = 'http://always.appstate.edu';
        $vars['contact_email'] = \Settings::get('always', 'contact_email');
        $template = new \Template($vars);
        $template->setModuleTemplate('always', 'Admin/Parents/Invitation.html');

        $body = $template->get();

        $message = \Swift_Message::newInstance()->setContentType('text/html')
                ->setSubject($subject)
                ->setFrom(array($from_email => $from_full_name))
                ->setTo($to_email)
                ->setBody($body);

        $transport = \Swift_MailTransport::newInstance();

        $mailer = \Swift_Mailer::newInstance($transport);

        $result = $mailer->send($message);
    }

    public function post(\Request $request)
    {
        $this->loadParent($request);

        try {
            switch ($request->getVar('command')) {
                case 'save':
                    $this->saveParent($request);
                    $this->emailParent();
                    break;

                case 'delete':
                    $this->deleteParent();
                    break;
            }
        } catch (\Exception $e) {
            if ($e instanceof \always\UserException) {
                $this->sendMessage($e->getMessage());
            } else {
                throw $e;
            }
        }
        $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
        return $response;
    }

    private function sendMessage($message)
    {
        \Session::getInstance()->always_message = $message;
    }

    private function createNewUser($username, $full_name)
    {
        $username = strtolower($username);
        $user = new \PHPWS_User;

        if ($user->isDuplicateUsername($username)) {
            throw new \always\UserException('User already in system');
        }

        $user->username = $user->email = $username;
        $user->display_name = $full_name;

        if ($user->isDuplicateEmail()) {
            throw new \always\UserException('Email address already in system');
        }

        $user->setActive(1);
        $user->setApproved(1);
        $user->save();

        $this->parent->createPassword();
        $password_hash = md5($user->username . $this->parent->getPassword());

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
        }
        $template->add('menu', $this->menu->get());

        if (!empty(\Session::getInstance()->always_message)) {
            $ses = \Session::getInstance();
            $template->add('message', $ses->always_message);
            unset($ses->always_message);
        }
        return $template;
    }

    private function loadMenu()
    {
        $this->menu = new \always\Menu('parents');
    }

    /**
     * Listing of all parents in system.
     * @param \Request $request
     * @return \Template
     */
    private function listing($request)
    {
        \Pager::prepare();
        javascript('jquery_ui');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/Parents/script.js'></script>");
        \Layout::addStyle('always', 'style.css');
        $parent = new \always\Resource\Parents;

        $form = $parent->pullForm();
        $form->requiredScript();
        $form->appendCSS('bootstrap');
        $form->setAction('always/admin/parents');
        $form->addHidden('command', 'save');
        $form->addHidden('parent_id', 0);

        //if (!$parent->getId()) {
        $form->addEmail('username')->setPlaceholder("Enter parent's email address")->setRequired();
        //}

        $form->getSingleInput('first_name')->setRequired();
        $form->getSingleInput('last_name')->setRequired();

        $form->addTextField('student_first_name')->setRequired()->setLabel('First name');
        $form->addTextField('student_middle_name')->setLabel('Middle name');
        $form->addTextField('student_last_name')->setRequired()->setLabel('Last name');

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
        $pager = new \DatabasePager($db);
        $pager->setHeaders(array('last_name', 'first_name', 'username'));
        $tbl_headers['last_name'] = $last_name;
        $tbl_headers['first_name'] = $first_name;
        $tbl_headers['username'] = $username;
        $pager->setTableHeaders($tbl_headers);
        $pager->setId('parent-list');
        $pager->setRowIdColumn('id');
        $pager->showQuery();
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
        \always\Factory\ParentFactory::deleteParentById($request->getVar('parent_id'));
    }

    private function getJsonProfileListing($parent_id)
    {
        $content = array();
        $profiles = \always\Factory\ProfileFactory::getProfilesByParentId($parent_id);
        if (empty($profiles)) {
            return 'No profiles found for this parent';
        }

        $content[] = '<ul style="margin-left:4px;padding-left:0px;list-style-type:none">';
        foreach ($profiles as $p) {
            $content[] = '<li>' . $p->getFullName() . ' - Class of ' . $p->getClassDate() . ' <a class="btn btn-default btn-sm" href="always/admin/profiles/update?parent_id='
                    . $parent_id . '&amp;original_id=' . $p->getOriginalId() . '">Update</a> <a class="btn btn-default btn-sm" href="always/' . $p->getPName() . '">View</a></li>';
        }
        $content[] = '</ul>';
        return implode("\n", $content);
    }

    private function editParentJson(\Request $request)
    {
        $parent = \always\Factory\ParentFactory::getParentById($request->getVar('parent_id'));
        $data['first_name'] = $parent->getFirstName();
        $data['last_name'] = $parent->getLastName();
        $data['username'] = $parent->getUsername();
        $data['profile_list'] = $this->getJsonProfileListing($parent->getId());
        return $data;
    }

}

?>
