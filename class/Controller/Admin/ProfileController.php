<?php

namespace always\Controller\Admin;

/**
 * Profile controller for admins.
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class ProfileController extends \Http\Controller {

    /**
     * @var \always\Resource\Profile
     */
    private $profile;

    /**
     * @var \always\Resource\Parents
     */
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
        $this->loadProfile($request);
        switch ($cmd) {
            case 'new':
                $this->loadParent($request);
                break;

            case 'update':
                $this->loadParentFromProfile();
                break;
        }
        $this->postProfile($request);

        if ($this->profile->isApproved()) {
            $forward_url = \Server::getSiteUrl() . 'always/' . $this->profile->getPname();
        } else {
            $forward_url = \Server::getSiteUrl() . 'always/admin/';
        }
        $response = new \Http\TemporaryRedirectResponse($forward_url);
        return $response;
    }

    private function postProfile(\Request $request)
    {
        \always\Factory\ProfileFactory::post($request, $this->profile,
                $this->parent);
        if ($request->isVar('save_published')) {
            $this->profile->setApproved(true);
        }
        \always\Factory\ProfileFactory::save($this->profile);
    }

    public function getHtmlView($data, \Request $request)
    {
        \Layout::addStyle('always', 'Admin/Profile/style.css');
        $this->loadMenu('profile');
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'list';
        }
        switch ($cmd) {
            case 'new':
                $this->loadParent($request);
                $this->loadProfile($request);
                $this->profile->setParentId($this->parent->getId());
                return $this->form();
                break;

            case 'list':
                $template = $this->listing();
                break;

            case 'update':
                $template = $this->updateCurrent($request);
                break;

            case 'view':
                return $this->view($request);
                break;

            case 'diff':
                return $this->diff($request);
                break;

            case 'approve':
                $this->approve($request);
                $forward_url = \Server::getSiteUrl() . 'always/admin/profiles/';
                \Server::forward($forward_url);
                break;
        }
        $template->add('menu', $this->menu->get());
        return $template;
    }

    private function approve(\Request $request)
    {
        $this->loadProfile($request);
        $this->profile->setApproved(1);
        $this->profile->save();
    }

    private function diff(\Request $request)
    {
        javascript('jquery');
        \Layout::addJSHeader("<script type='text/javascript' src='" .
                PHPWS_SOURCE_HTTP . "mod/always/javascript/Profiles/diff.js'></script>");
        $this->loadProfile($request);

        $compare = \always\Factory\ProfileFactory::getProfileByOriginalId($this->profile->getOriginalId(),
                        true);

        if (empty($compare)) {
            throw new \Exception('Nothing to compare against. New profile.');
        }

        $tpl = \always\Factory\ProfileFactory::diff($compare, $this->profile);
        $template = new \Template($tpl);
        $template->setModuleTemplate('always', 'Admin/Profile/Diff.html');
        $template->add('profile_id', $this->profile->getId());
        return $template;
    }

    private function view(\Request $request)
    {
        $this->loadProfile($request);
        $viewtpl = \always\Factory\ProfileFactory::display($this->profile);
        // zero version has nothing to diff against
        $variables['can_diff'] = $this->profile->getVersion() > 0 ? 1 : 0;
        $variables['needs_approval'] = !$this->profile->isApproved();
        $variables['content'] = $viewtpl->get();
        $variables['profile_id'] = $this->profile->getId();
        $template = new \Template($variables);
        $template->setModuleTemplate('always', 'Admin/Profile/Approve.html');
        return $template;
    }

    private function loadMenu()
    {
        $this->menu = new \always\Menu('profiles');
    }

    private function form()
    {
        return \always\Factory\ProfileFactory::form($this->profile);
    }

    private function listing()
    {
        \Pager::prepare();
        javascript('jquery_ui');
        /*
          \Layout::addJSHeader("<script type='text/javascript' src='" .
          PHPWS_SOURCE_HTTP . "mod/always/javascript/Profiles/script.js'></script>");
         *
         */
        $data = array();
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Admin/Profile/List.html');
        return $template;
    }

    private function loadProfile(\Request $request)
    {
        if ($request->isVar('profile_id') && $request->getVar('profile_id')) {
            $this->profile = \always\Factory\ProfileFactory::getProfileById($request->getVar('profile_id'));
        } else {
            $this->profile = new \always\Resource\Profile;
        }
    }

    private function loadParentFromProfile()
    {
        $this->parent = \always\Factory\ParentFactory::getParentById($this->profile->getParentId());
    }

    private function loadParent(\Request $request)
    {
        if ($request->isVar('parent_id')) {
            $this->parent = \always\Factory\ParentFactory::getParentById($request->getVar('parent_id'));
        } else {
            $this->parent = new \always\Resource\Parents;
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
        $profile = \always\Factory\ProfileFactory::getProfileByOriginalId($original_id);

        if (!$profile->isApproved()) {
            if ($profile->isSubmitted()) {
                // profile was submitted and is awaiting approval, force view
                // so admin may approve
                return \always\Factory\ProfileFactory::display($profile);
            } elseif (!$profile->isFirst()) {
                // not the first version, not approved and not submitted.
                // Since this is a work in progress from the parent we pull the
                // last approved profile
                $profile = \always\Factory\ProfileFactory::getProfileByOriginalId($original_id,
                                true);
            }
        }

        $template = \always\Factory\ProfileFactory::form($profile);

        return $template;
    }

    private function pagerData()
    {
        extract(\always\Factory\ProfileFactory::getLastVersionDB(false));
        $profile_table->addFieldConditional('submitted', 1);
        $profile_table->addFieldConditional('approved', 0);
        $pager = new \DatabasePager($db);
        $pager->setCallback(array('\always\Controller\Admin\ProfileController', 'parseRow'));
        $pager->setHeaders(array('last_name' => 'Full name', 'last_updated' => 'Last updated'));
        $tbl_headers['last_name'] = $profile_table->getField('last_name');
        $tbl_headers['last_updated'] = $profile_table->getField('last_updated');
        $pager->setTableHeaders($tbl_headers);
        $pager->setId('parent-list');
        $pager->setRowIdColumn('id');
        return $pager->getJson();
    }

    public static function parseRow($row)
    {
        $profile_id = $row['id'];
        $row['full_name'] = $row['last_name'] . ', ' . $row['first_name'];
        if ($row['last_updated']) {
            $row['last_updated'] = strftime('%c', $row['last_updated']);
        } else {
            $row['last_updated'] = 'Never';
        }
        $row['action'] = <<<EOF
<a href="always/admin/profiles/view?profile_id=$profile_id" class="btn btn-sm btn-default">View</a>
EOF;
        if ($row['version'] > 1) {
            $row['action'] .= <<<EOF
 <a href="always/admin/profiles/diff?profile_id=$profile_id" class="btn btn-sm btn-default"><i class="fa fa-exchange"></i> Diff</a>
EOF;
        }
        return $row;
    }

    protected function getJsonView($data, \Request $request)
    {
        if ($request->isVar('command')) {
            switch ($request->getVar('command')) {
                case 'pager':
                    $data = $this->pagerData();
                    break;
            }
        } else {
            throw new \Exception('JSON command not found');
        }
        return parent::getJsonView($data, $request);
    }

}

?>
