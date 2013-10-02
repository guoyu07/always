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

    public function post(\Request $request)
    {
        $this->loadCurrentParent();
        $id = $request->getVar('id');
        if ($id) {
            $profile = \always\ProfileFactory::getProfileById($id);
        } else {
            $profile = new \always\Profile;
        }

        if ($request->isUploadedFile('profile_pic')) {
            $file = $request->getUploadedFileArray('profile_pic');
            $image_path = \always\ProfileFactory::saveProfileImage($file,
                            $this->parent, $profile);
            $profile->setProfilePic($image_path);
        }


        $profile->setFirstName($request->getVar('first_name'));
        $profile->setLastName($request->getVar('last_name'));
        $profile->setClassDate($request->getVar('class_date'));
        $profile->setSummary($request->getVar('summary'));
        $profile->setStory($request->getVar('story'));
        $profile->setParentId($this->parent->getId());

        if ($request->isVar('submit_profile')) {
            $profile->setSubmitted(true);

            ///// This is a cheat for until we get approval worked out
            /* @todo Build approval and remove this line */
            $profile->setApproved(true);
        } else {
            $profile->setSubmitted(false);
        }

        \always\ProfileFactory::saveProfile($profile);

        $link = $profile->getViewUrl();
        $response = new \Http\SeeOtherResponse($link);
        return $response;
    }

    private function loadCurrentParent()
    {
        $this->parent = \always\ParentFactory::getCurrentParent();
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

            case 'edit':
                return \always\ProfileFactory::editCurrentUserProfile();
                break;
        }
    }

    private function view()
    {
        $profile = \always\ProfileFactory::getCurrentUserProfile();

        if ($profile->isSaved()) {
            return \always\ProfileFactory::displayProfile($profile);
        } else {
            // no profile was ever created or it hasn't been approved
            \Server::forward('/always/parent/edit');
        }
    }

}
?>
