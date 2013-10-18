<?php

namespace always\Controller\Admin;

/**
 * Profile controller for admins.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ProfileController extends \Http\Controller {

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
        $cmd = $request->shiftCommand();

        switch ($cmd) {
            case 'update':
                $this->loadProfile($request);
                $this->loadParentFromProfile();
                $this->postProfile($request);
                if ($this->profile->isApproved()) {
                    $forward_url = \Server::getSiteUrl() . '/always/' . $this->profile->getPname();
                } else {
                    $forward_url = \Server::getSiteUrl() . '/always/admin/';
                }
                $response = new \Http\TemporaryRedirectResponse($forward_url);
                break;
        }

        return $response;
    }

    private function postProfile(\Request $request)
    {
        \always\ProfileFactory::post($request, $this->profile, $this->parent);
        \always\ProfileFactory::save($this->profile);
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
                $this->profile->setParentId($request->getVar('parent_id'));
                return $this->form();
                break;

            case 'list':
                $template = $this->listing();
                break;

            case 'update':
                $template = $this->updateCurrent($request);
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
        return \always\ProfileFactory::update($this->profile);
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

    private function loadParentFromProfile()
    {
        $this->parent = \always\ParentFactory::getParentById($this->profile->getParentId());
    }

    private function loadParent(\Request $request)
    {
        if ($request->isVar('parent_id')) {
            $this->parent = \always\ParentFactory::getParentById($request->getVar('parent_id'));
        } else {
            $this->parent = new \always\Parents;
        }
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
    private function updateCurrent(\Request $request)
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

        $template = \always\ProfileFactory::update($profile);

        return $template;
    }

}

?>
