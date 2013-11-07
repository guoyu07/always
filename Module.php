<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Module extends \Module implements \SettingDefaults{

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
        } elseif ($cmd == 'parent' && \Current_User::isLogged()) {
            $parents = new \always\Controller\Parents($this);
            return $parents;
        } else {
            $guest = new \always\Controller\Guest($this);
            return $guest;
        }
        return $controller;
    }

    public function runTime(\Request $request)
    {
        \Layout::addStyle('always');
        $module = $request->getModule();
        if (empty($module)) {
            $template = Controller\Guest::welcome();
            \Layout::add($template->get());
        }
    }

    public function getSettingDefaults()
    {
        $array['contact_email'] = 'replaceme@notarealsite.com';
        return $array;
    }

}

?>
