<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Module extends \Module {

    public function __construct()
    {
        parent::__construct();
        $this->setTitle('always');
        $this->setProperName(t('Always'));
    }

    public function getController(\Request $request)
    {
        $cmd = $request->shiftCommand();
        if ($cmd == 'admin' && \Current_User::allow('always')) {
            $admin = new \always\Controller\Admin($this);
            $controller = $admin->getController($request);
        } elseif ($cmd == 'parent') {
            $parents = new \always\Controller\Parents($this);
            $controller = $parents->getController($request);
        } else {
            $guest = new \always\Controller\Guest($this);
            $controller = $guest->getController($request);
        }
        return $controller;
    }

}

?>
