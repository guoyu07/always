<?php

namespace always\Controller\Admin;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Profiles extends \Http\Controller {

    private $profile;
    private $parent;

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function post(\Request $request)
    {

    }

    public function getHtmlView($data, \Request $request)
    {
        $this->loadMenu();
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'list';
        }

        switch ($cmd) {
            case 'new':
                $this->loadProfile($request);
                $this->loadParent($request);
                return $this->form();
                break;

            case 'list':
                $template = $this->listing();
                break;
        }
        $template->add('menu', $this->menu->get());
        return $template;
    }

    private function loadMenu()
    {
        $this->menu = new \always\Menu('profiles');
    }

    private function form()
    {
        return \always\ProfileFactory::editProfile($this->profile, $this->parent);
    }

    private function listing()
    {
        \Pager::prepare();
        $data = array();
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Admin/Profile/List.html');
        return $template;
    }

    private function loadProfile(\Request $request)
    {
        if ($request->isVar('profile_id')) {
            $this->profile = \always\ProfileFactory::getProfileById($request->getVar('profile_id'));
        } else {
            $this->profile = new \always\Profile;
        }
    }

    private function loadParent(\Request $request)
    {
        if ($request->isVar('parent_id')) {
            $this->parent = \always\ParentFactory::getParentById($request->getVar('parent_id'));
        } else {
            $this->parent = new \always\Parents;
        }
    }

}

?>
