<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Menu {

    private $active;

    public function __construct($active)
    {
        $this->active = $active;
    }

    public function get()
    {
        $template = new \Template;
        switch ($this->active) {
            case 'parents':
                $template->add('parents_active', 1);
                break;
            case 'profiles':
                $template->add('profiles_active', 1);
                break;
            case 'settings':
                $template->add('settings_active', 1);
                break;
        }
        $template->setModuleTemplate('always', 'Admin/Menu.html');
        return $template->get();
    }

    public function __toString()
    {
        return $this->get();
    }
}

?>
