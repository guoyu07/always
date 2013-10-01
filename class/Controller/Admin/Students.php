<?php

namespace always\Controller\Admin;

/**
 * The controller for student administration.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Students extends \Http\Controller {

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
        $student = new \always\Student;
        $student_id = $request->getVar('student_id');
        if ($student_id) {
            $student->id = $student_id;
            \ResourceFactory::loadByID($student);
        }


        switch ($request->getVar('command')) {
            case 'save':
                $student->first_name = $request->getVar('first_name');
                $student->last_name = $request->getVar('last_name');
                $student->student_fname = $request->getVar('student_fname');
                $student->student_lname = $request->getVar('student_lname');
                $student->class_date = $request->getVar('class_date');

                $this->createNewUser($request->getVar('username'), $student);
                \ResourceFactory::saveResource($student);
                break;

            case 'delete':
                $this->deleteStudent($student);
                break;

            case 'approve';
                exit('approve not written');
                $student = $this->getStudent($student->id);
                \ResourceFactory::saveResource($student);
                break;
        }
        $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
        return $response;
    }

    private function createNewUser($username, $student)
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

        $user->created = time();
        $user->updated = time();
        $user->setActive(1);
        $user->setApproved(1);
        $user->authorize = 1;
        $user->save();

        $password = randomString();
        $password_hash = md5($user->username . $password);

        $user->password = $password;

        $auth = new local_authorization($user);
        $auth->createUser();

        PHPWS_Core::initModClass('users', 'Action.php');
        User_Action::assignDefaultGroup($user);

        $db = \Database::newDB();
        $pw = $db->addTable('always_pw');
        $pw->addValue('username', $user->username);
        $pw->addValue('u', $user->username);


    }

    public function getHtmlView($data, \Request $request)
    {
        // JQuery called in prepare
        \Pager::prepare();
        javascript('jquery');
        javascript('jquery_ui');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/Student/script.js'></script>");
        \Layout::addStyle('always', 'style.css');

        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'list';
        }

        switch ($cmd) {
            case 'list':
                return $this->studentList($request);
                break;

            default:
                $template = new \Template();
                $template->setModuleTemplate('always',
                        'Admin/Students/Student.html');
                $data = array_merge((array) $data,
                        (array) $this->getStudent($cmd));
                $template->addVariables($data);
                return $template;
                break;
        }
    }

    private function studentList($request)
    {
        $student = new \always\Student;
        $form = $student->pullForm();
        $form->appendCSS('bootstrap');
        $form->setAction('/always/admin/students');
        $form->addHidden('command', 'save');
        $form->addHidden('student_id', 0);

        if (!$student->getId()) {
            $username = $form->addEmail('username');
            $username->setPlaceholder("Enter parent's email address");
        }

        $form->getSingleInput('first_name')->setRequired();
        $form->getSingleInput('last_name')->setRequired();
        $form->getSingleInput('student_fname')->setRequired()->setLabel('First name');
        $form->getSingleInput('student_lname')->setRequired()->setLabel('Last name');
        $form->getSingleInput('class_date')->setFirstBlank();

        $form->addSubmit('submit', 'Save student');
        $data = $form->getInputStringArray();
        $data['menu'] = $this->menu->get($request);

        //var_dump($data);

        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Admin/Students/List.html');
        return $template;
    }

    protected function getJsonView($data, \Request $request)
    {
        if ($request->isVar('command')) {
            switch ($request->getVar('command')) {
                case 'student':
                    $data['student'] = $this->getStudent($request->getVar('id'));
                    break;
            }
        } else {
            $db = \Database::newDB();
            $student = $db->addTable('always_student');
            $id = $student->addField('id');
            $first_name = $student->addField('first_name');
            $last_name = $student->addField('last_name');
            $class_date = $student->addField('class_date');
            $db->setGroupBy($last_name);
            $pager = new \DatabasePager($db);
            $pager->setHeaders(array('last_name', 'first_name', 'class_date'));
            $tbl_headers['last_name'] = $last_name;
            $tbl_headers['first_name'] = $first_name;
            $tbl_headers['class_date'] = $class_date;
            $pager->setTableHeaders($tbl_headers);
            $pager->setId('student-list');
            $pager->setRowIdColumn('id');
            $data = $pager->getJson();
        }
        return parent::getJsonView($data, $request);
    }

    private function getStudent($student_name)
    {
        $name = explode('-', $student_name);
        $db = \Database::newDB();
        $co = $db->addTable('always_student');
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
