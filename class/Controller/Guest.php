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
        $cmd = $request->lastCommand();

        if (empty($cmd)) {
            $cmd = 'welcome';
        }

        switch ($cmd) {
            case 'welcome':
                return self::welcome();
                break;

            default:
                $profile = \always\ProfileFactory::getProfileByName($cmd);
                // Profile not found
                if (empty($profile)) {
                    throw new \Http\NotFoundException;
                }
                if ($profile->isSaved()) {
                    return \always\ProfileFactory::displayProfile($profile);
                }

                $response = new \Http\NotFoundResponse;
                return new \View\HtmlErrorView($request, $response);
        }
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
            $parent = \always\ParentFactory::getCurrentParent();
            if ($parent->id) {
                $data['parent'] = true;
                $profile = \always\ProfileFactory::getCurrentUserProfile(false);
                if ($profile->isSaved()) {
                    $data['student_address'] = $profile->getViewUrl();
                    $data['button'] = 'View ' . $profile->getFullName();
                } else {
                    $data['student_address'] = 'always/parent/';
                    $data['button'] = 'Create a new profile';
                }
            }
        }
        $template = new \Template();
        $template->setModuleTemplate('always', 'Guest/Welcome.html');
        $template->addVariables($data);
        return $template;
    }

}

?>
