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
        // The last command was stripped and wasn't recognized as a parent
        // or an admin. In this case, we get lastCommand instead of stripping
        // another
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
                    return \always\ProfileFactory::display($profile);
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
            }
        }
        $template = new \Template();
        $template->setModuleTemplate('always', 'Guest/Welcome.html');
        $template->addVariables($data);
        return $template;
    }

}

?>
