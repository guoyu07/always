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
                if ($profile->isSaved()) {
                    return \always\ProfileFactory::displayProfile($profile);
                }

                $response = new \Http\NotFoundResponse;
                return new \View\HtmlErrorView($request, $response);
        }
    }

    /*
    private function view()
    {
        $template = new \Template();
        $template->setModuleTemplate('always', 'Parents/View.html');
        $data = array();
        $template->addVariables($data);
        return $template;
    }
*/
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
                    $data['button'] = 'View ' . $profile->getFullName();
                } else {
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
