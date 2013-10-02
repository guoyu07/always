<?php

namespace always\Controller\Parents;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Profile extends \Http\Controller {

    private $parents;
    private $profile;

    public function __construct()
    {
        $this->loadParent();
    }

    public function loadParent()
    {
        $this->parents = new \always\Parents;

        $user_id = \Current_User::getId();
        $db = \Database::newDB();
        $parents = $db->addTable('always_parents');
        $db->setConditional($parents->getFieldConditional('user_id', $user_id));
        $db->selectInto($this->parents);
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
        $db->addConditional($pt->getFieldConditional('parent_id', $this->parents->id));
        $db->addConditional($pt->getFieldConditional('approved', 1));
        $db->setLimit(1);
        $db->selectInto($profile);
        $this->profile = $profile;
    }

}

?>
