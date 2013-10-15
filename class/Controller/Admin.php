<?php

namespace always\Controller;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Admin extends \Http\Controller {

    public function getController(\Request $request)
    {
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'parents';
        }

        $controllers = array(
            'parents' => '\always\Controller\Admin\ParentController',
            'profiles' => '\always\Controller\Admin\ProfileController',
            'settings' => '\always\Controller\Admin\SettingsController'
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
