<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Parents extends \Http\Controller {

    private $parent;
    private $profile;

    public function get(\Request $request)
    {
        $this->loadCurrentParent();
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function post(\Request $request)
    {
        $this->loadCurrentParent();
        $this->loadProfile($request);

        \always\Factory\ProfileFactory::post($request, $this->profile,
                $this->parent);
        $this->profile->setApproved(false);
        if ($request->isVar('save_unpublished')) {
            $this->profile->setSubmitted(false);
        } else {
            $this->profile->setSubmitted(true);
        }

        \always\Factory\ProfileFactory::save($this->profile);
        $forward_url = \Server::getSiteUrl() . 'always/' . $this->profile->getPname();
        $response = new \Http\TemporaryRedirectResponse($forward_url);
        return $response;
    }

    private function loadProfile(\Request $request)
    {
        if ($request->isVar('profile_id')) {
            $this->profile = \always\Factory\ProfileFactory::getProfileById($request->getVar('profile_id'));
        } else {
            $this->profile = new \always\Resource\Profile;
        }
    }

    private function loadCurrentParent()
    {
        $this->parent = \always\Factory\ParentFactory::getCurrentParent();
    }

    public function getHtmlView($data, \Request $request)
    {
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'view';
        }
        switch ($cmd) {
            case 'list':
                return $this->listing();
                break;

            case 'view':
                return $this->view();
                break;

            case 'update':
                return $this->update($request);
                break;

            case 'publish':
                return $this->publish($request);
                break;

            case 'gallery':
                return $this->gallery();
                break;
        }
    }

    private function view()
    {
        $profile = \always\Factory\ProfileFactory::getCurrentUserProfile();

        if ($profile->isSaved()) {
            return \always\Factory\ProfileFactory::display($profile);
        } else {
            // no profile was ever created or it hasn't been approved
            \Server::forward('/always/parent/edit');
        }
    }

    private function publish(\Request $request)
    {
        if (!$request->isVar('profile_id')) {
            throw new \Exception('Profile id not set');
        }

        $profile = \always\Factory\ProfileFactory::getProfileById($request->getVar('profile_id'));
        $profile->setSubmitted(1);
        \always\Factory\ProfileFactory::save($profile);

        $forward_url = \Server::getSiteUrl() . 'always/' . $profile->getPname();
        $response = new \Http\TemporaryRedirectResponse($forward_url);
        $response->forward();
    }

    private function update(\Request $request)
    {
        $profile = \always\Factory\ProfileFactory::getUnpublishedProfile($request->getVar('original_id'));
        return \always\Factory\ProfileFactory::form($profile);
    }

    private function listing()
    {
        $data = array();
        $profiles = \always\Factory\ProfileFactory::getProfilesByParentId($this->parent->getId());
        foreach ($profiles as $pf) {
            $pic = $pf->getProfilePic();
            if ($pic) {
                $sub['profile_pic'] = $pic->getSrc();
            }

            $sub['pname'] = $pf->getPname();
            $sub['name'] = $pf->getFullName();
            $sub['summary'] = $pf->getSummary();
            $sub['original_id'] = $pf->getOriginalId();
            $sub['profile_id'] = $pf->getId();
            $sub['publish'] = !$pf->isSubmitted();
            $data['listing'][] = $sub;
        }

        $profiles = \always\Factory\ProfileFactory::getCurrentUserProfiles();
        $template = new \Template($data);
        $template->setModuleTemplate('always', 'Parents/ProfileList.html');
        return $template;
    }

}

?>
