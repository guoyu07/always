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
        $student->id = $request->getVar('student_id');
        switch ($request->getVar('command')) {
            case 'save':
                $student->first_name = $request->getVar('first_name');
                $student->last_name = $request->getVar('last_name');
                $student->class_date = $request->getVar('class_date');
                $student->bg = $request->getVar('bg');
                $student->profile_pic = $request->getVar('profile_pic');
                $student->story = $request->getVar('story');
                $student->summary = $request->getVar('summary');
                $student->submitted = 1;
                \ResourceFactory::saveResource($student);
                break;

            case 'delete':
                $this->deleteStudent($student);
                break;

            case 'approve';
                $student = $this->getStudent($student->id);
                $student->submitted = 0;
                $student->live_profile_pic = $student->profile_pic;
                $student->live_summary = $student->summary;
                $student->live_story = $student->story;
                \ResourceFactory::saveResource($student);
                break;
        }
        $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
        return $response;
    }

    public function getHtmlView($data, \Request $request)
    {
        // JQuery called in prepare
        \Pager::prepare();
        javascript('jquery_ui');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/Student/script.js'></script>");
        \Layout::addStyle('always', 'style.css');
        $data['menu'] = $this->menu->get($request);

        $cmd = $request->shiftCommand();

        $template = new \Template();

        switch ($cmd) {
            case 'admin':
                $template->setModuleTemplate('always',
                        'Admin/Students/List.html');
                break;

            default:
                $template->setModuleTemplate('always',
                        'Admin/Students/Student.html');
                $data = array_merge((array) $data,
                        (array) $this->getStudent($cmd));
                break;
        }
        $template->addVariables($data);
        //var_dump($template);
        //break;
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
            //$ct = $db->addTable('rd_ctocollege');
            //$col_id = $ct->addField('college_id', 'assigned');
            //$col_id->showCount();
            //$db->join($college->getField('id'), $col_id, 'left');
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
