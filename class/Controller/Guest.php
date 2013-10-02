<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Guest extends \Http\Controller {

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
            $cmd = 'welcome';
        }

        switch ($cmd) {
            case 'welcome':
                return self::welcome();
                break;

            case 'view':
                return $this->view();
                break;
        }
    }

    private function view()
    {
        $template = new \Template();
        $template->setModuleTemplate('always', 'Parents/View.html');
        $data = array('blah' => 'blah');
        $template->addVariables($data);
        return $template;
    }

    /**
     * Called by the runTime in Module to appear on front page
     * @return \Template
     */
    public static function welcome()
    {
        $data = array();
        $data['parent'] = false;
        if (\Current_User::isLogged()) {
            $parent = \always\Controller\Parents::getCurrentParent();
            if ($parent->id) {
                $data['parent'] = true;
                $data['student_fname'] = $parent->student_fname;
                $data['student_lname'] = $parent->student_lname;
            }
        }
        $template = new \Template();
        $template->setModuleTemplate('always', 'Guest/Welcome.html');
        $template->addVariables($data);
        return $template;
    }

}

?>
