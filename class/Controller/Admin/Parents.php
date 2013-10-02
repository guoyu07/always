<?php

namespace always\Controller\Admin;

/**
 * The controller for parent administration.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Parents extends \Http\Controller {

    private $menu;


    public function __construct(\Module $module)
    {
        parent::__construct($module);
        $this->menu = new \always\Menu;
    }

    public function get(\Request $request)
    {
        $nrequest = $request->getNextRequest();
        $token = $nrequest->getCurrentToken();
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function post(\Request $request)
    {
        $parent = new \always\Parents;
        $parent_id = $request->getVar('parent_id');
        if ($parent_id) {
            $parent->id = $parent_id;
            \ResourceFactory::loadByID($parent);
        }


        switch ($request->getVar('command')) {
            case 'save':
                $parent->first_name = $request->getVar('first_name');
                $parent->last_name = $request->getVar('last_name');

                $new_user_id = $this->createNewUser($request->getVar('username'), $parent);
                $parent->user_id = $new_user_id;
                \ResourceFactory::saveResource($parent);
                break;

            case 'delete':
                $this->deleteParent($parent);
                break;

            case 'approve';
                exit('approve not written');
                $parent = $this->getParent($parent->id);
                \ResourceFactory::saveResource($parent);
                break;
        }
        $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
        return $response;
    }

    private function createNewUser($username, $parent)
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
        // JQuery called in prepare
        \Pager::prepare();
        javascript('jquery');
        javascript('jquery_ui');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/Parents/script.js'></script>");
        \Layout::addStyle('always', 'style.css');

        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'list';
        }

        switch ($cmd) {
            case 'list':
                return $this->parentList($request);
                break;

            default:
                $template = new \Template();
                $template->setModuleTemplate('always',
                        'Admin/Parents/Parent.html');
                $data = array_merge((array) $data,
                        (array) $this->getParent($cmd));
                $template->addVariables($data);
                return $template;
                break;
        }
    }

    private function parentList($request)
    {
        $parent = new \always\Parents;
        $form = $parent->pullForm();
        $form->appendCSS('bootstrap');
        $form->setAction('/always/admin/parents');
        $form->addHidden('command', 'save');
        $form->addHidden('parent_id', 0);

        if (!$parent->getId()) {
            $username = $form->addEmail('username');
            $username->setPlaceholder("Enter parent's email address");
        }

        $form->getSingleInput('first_name')->setRequired();
        $form->getSingleInput('last_name')->setRequired();

        $form->addSubmit('submit', 'Save parent');
        $data = $form->getInputStringArray();

        //var_dump($data);

        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Admin/Parents/List.html');
        return $template;
    }

    protected function getJsonView($data, \Request $request)
    {
        if ($request->isVar('command')) {
            switch ($request->getVar('command')) {
                case 'parent':
                    $data['parent'] = $this->getParent($request->getVar('id'));
                    break;
            }
        } else {
            $db = \Database::newDB();
            $parent = $db->addTable('always_parents');
            $id = $parent->addField('id');
            $first_name = $parent->addField('first_name');
            $last_name = $parent->addField('last_name');
            $db->setGroupBy($last_name);
            $pager = new \DatabasePager($db);
            $pager->setHeaders(array('last_name', 'first_name'));
            $tbl_headers['last_name'] = $last_name;
            $tbl_headers['first_name'] = $first_name;
            $pager->setTableHeaders($tbl_headers);
            $pager->setId('parent-list');
            $pager->setRowIdColumn('id');
            $data = $pager->getJson();
        }
        return parent::getJsonView($data, $request);
    }

    private function getParent($parent_name)
    {
        $name = explode('-', $parent_name);
        $db = \Database::newDB();
        $co = $db->addTable('always_parents');
        $db->setConditional($co->getFieldconditional('first_name', $name[0]));
        $db->setConditional($co->getFieldconditional('last_name', $name[1]));
        $result = $db->select();

        if (empty($result[0])) {
            return null;
        }

        if ($result[0]['submitted']) {
            $result[0]['approve'] = '<button type="submit" class="btn btn-primary" name="command" value="approve" />Approve</button>';
        }
        else
            $result[0]['approve'] = "";

        return $result[0];
    }

}

?>
