<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Parents extends \Http\Controller {

    public function getController(\Request $request)
    {
        // we used shiftCommand in the Module. Here we use token because
        // because we only want one command pulled from the url
        $cmd = $request->getCurrentToken();
        if (empty($cmd)) {
            $cmd = 'welcome';
        }

        $controllers = array(
            'welcome' => '\always\Controller\User\Welcome',
            'profile' => '\always\Controller\User\Profile'
        );

        if (!array_key_exists($cmd, $controllers)) {
            throw new \Http\NotFoundException($request);
        }

        $class = $controllers[$cmd];
        $controller = new $class($this->getModule());
        return $controller;
    }

    public static function getCurrentParent()
    {
        $profile = new \always\Parent;
        $db = \Database\newDB();
        $as = $db->addTable('always_student');
        $as->addFieldConditional('user_id', \Current_User::getId());
        $db->
    }

}

?>
