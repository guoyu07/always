<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class User extends \Http\Controller {

    public function getController(\Request $request)
    {
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            if (!\Current_User::isLogged()) {
                $cmd = 'welcome';
            } else {
                $cmd = 'profile';
            }
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

}

?>
