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

            case 'list':
                return $this->listing($request);
                break;

            case 'search':
                return $this->listing($request);
                break;

            default:
                $profile = \always\Factory\ProfileFactory::getProfileByName($cmd);

                // Profile not found
                if (empty($profile)) {
                    throw new \Http\NotFoundException;
                }

                $parent = \always\Factory\ParentFactory::getParentById($profile->getParentId());
                if ($profile->isApproved() || $parent->getUserId() == \Current_User::getId() || \Current_User::allow('always')) {
                    return \always\Factory\ProfileFactory::display($profile);
                } else {
                    $profile = \always\Factory\ProfileFactory::getLastApprovedByName($cmd);
                    if (empty($profile)) {
                        $tpl['message'] = 'Sorry, but there isn\'t a student profile available at this address.';
                        $template = new \Template($tpl);
                        $template->setModuleTemplate('always', 'Error.html');
                        return $template;
                    }
                    return \always\Factory\ProfileFactory::display($profile);
                }

                $response = new \Http\NotFoundResponse;
                return new \View\HtmlErrorView($request, $response);
        }
    }

    private function listing(\Request $request)
    {
        if ($request->isVar('always_search')) {
            $search_by = $request->getVar('always_search');
            if (preg_match('/[^\w\s\-\']/', $search_by)) {
                $search_by = null;
            } else {
                $data['limiter'] = 'Searching by name "' . $search_by . '"';
            }
        } else {
            $search_by = null;
        }

        if ($request->isVar('class_date')) {
            $class_date = $request->getVar('class_date');
            if (!is_numeric($class_date)) {
                $class_date = null;
            } else {
                $data['limiter'] = 'Searching by class date "' . $class_date . '"';
            }
        } else {
            $class_date = null;
        }

        $db = \Database::newDB();
        $ap = $db->addTable('always_profile', null, false);
        $db->addExpression(new \Database\Expression('distinct(' . $ap->getField('class_date'). ')'));
        $ap->addOrderBy('class_date');
        while($result = $db->selectColumn()) {
            $data['options'][] = $result;
        }

        $profiles = \always\Factory\ProfileFactory::getProfiles(true, $search_by, $class_date);
        $data['profiles'] = $profiles;
        $template = new \Template();
        $template->setModuleTemplate('always', 'Guest/List.html');
        $template->addVariables($data);
        return $template;
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
            $parent = \always\Factory\ParentFactory::getCurrentParent();
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
