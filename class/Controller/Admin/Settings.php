<?php

namespace always\Controller\Admin;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Settings extends \Http\Controller {

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function post(\Request $request)
    {

    }

    public function getHtmlView($data, \Request $request)
    {
        $this->loadMenu();
        $cmd = $request->shiftCommand();

        if (empty($cmd)) {
            $cmd = 'list';
        }

        switch ($cmd) {
        }
        //$template->add('menu', $this->menu->get());
        //return $template;
        return new \View\HtmlView('Settings');
    }

    private function loadMenu()
    {

    }

}

?>
