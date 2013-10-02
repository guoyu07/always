<?php

namespace always\Controller\User;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Profile extends \Http\Controller {

    private $student;
    private $profile;

    public function __construct()
    {
        $this->loadStudent();
    }

    public function loadStudent()
    {
        $this->student = new \always\Student;

        $user_id = \Current_User::getId();
        $db = \Database::newDB();
        $student = $db->addTable('always_student');
        $db->setConditional($student->getFieldConditional('user_id', $user_id));
        $db->selectInto($this->student);
    }

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function getHtmlView($data, \Request $request)
    {
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'view';
        }

        switch ($cmd) {
            case 'view':
                $this->loadCurrentProfile();
                return $this->view();
                break;
        }
    }

    private function view()
    {
        $data = $this->profile->getData();
        //var_dump($data);
        $template = new \Template();
        $template->setModuleTemplate('always', 'User/View.html');
        $data = array('blah' => 'blah');
        $template->addVariables($data);
        return $template;
    }

    private function loadCurrentProfile()
    {
        $profile = new \always\Profile;

        $db = \Database::newDB();
        $pt = $db->addTable('always_profile');
        $pt->addOrderBy($pt->getField('version'), 'desc');
        $db->addConditional($pt->getFieldConditional('student_id', $this->student->id));
        $db->addConditional($pt->getFieldConditional('approved', 1));
        $db->setLimit(1);
        $db->selectInto($profile);
        $this->profile = $profile;
    }

}

?>
