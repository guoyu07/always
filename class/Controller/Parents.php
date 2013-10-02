<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Parents extends \Http\Controller {

    private $parent;

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
                return $this->view();
                break;
        }
    }

    private function view()
    {
        $profile = \always\ProfileFactory::getCurrentProfile();
        $data = array();
        $template = new \Template();
        $template->setModuleTemplate('always', 'Parents/View.html');
        $template->addVariables($data);
        return $template;
    }

    public static function getCurrentParent()
    {
        $parent = new \always\Parents;
        $db = \Database::newDB();
        $ap = $db->addTable('always_parents');
        $ap->addFieldConditional('user_id', \Current_User::getId());
        $db->selectInto($parent);
        $this->parent = $parent;
        return $this->parent;
    }

}

?>
