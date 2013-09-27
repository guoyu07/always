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
        $nrequest = $request->getNextRequest();
        $token = $nrequest->getCurrentToken();

        if ($token == '/') {
            $token = 'students';
        }
        $controllers = array(
            'students'      => '\always\Controller\Admin\Students',
        );

        if (!array_key_exists($token, $controllers)) {
            throw new \Http\NotFoundException($request);
        }

        $class = $controllers[$token];
        $controller = new $class($this->getModule());
        return $controller;
    }

    public function getHtmlView($data, \Request $request){}

}

?>
