<?php

namespace always\Controller\Admin;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class SettingsController extends \Http\Controller {

    private $menu;

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function post(\Request $request)
    {
        $ce = $request->getVar('contact_email');
        \Settings::set('always', 'contact_email', $ce);
        $cp = $request->getVar('contact_phone');
        \Settings::set('always', 'contact_phone', $cp);
        $response = new \Http\SeeOtherResponse(\Server::getCurrentUrl(false));
        $this->sendMessage('Settings updated.');
        return $response;
    }

    public function getHtmlView($data, \Request $request)
    {
        $this->loadMenu('settings');
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'form';
        }

        switch ($cmd) {
            case 'form':
                $template = $this->form();
                break;
        }
        if (!empty(\Session::getInstance()->always_message)) {
            $ses = \Session::getInstance();
            $template->add('message', $ses->always_message);
            unset($ses->always_message);
        }
        $template->add('menu', $this->menu->get());
        return $template;
    }

    private function form()
    {
        $form = new \Form;
        $form->requiredScript();
        $form->appendCSS('bootstrap');
        $form->addEmail('contact_email',
                \Settings::get('always', 'contact_email'))->setRequired();
        $form->addTextField('contact_phone',
                \Settings::get('always', 'contact_phone'))->setRequired();
        $form->addSubmit('submit', 'Save settings');
        $vars = $form->getInputStringArray();
        $template = new \Template($vars);
        $template->setModuleTemplate('always', 'Admin/Settings/Form.html');
        return $template;
    }

    private function loadMenu($active)
    {
        $this->menu = new \always\Menu($active);
    }

    private function sendMessage($message)
    {
        \Session::getInstance()->always_message = $message;
    }

}

?>
