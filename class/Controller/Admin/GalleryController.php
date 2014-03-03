<?php

namespace always\Controller\Admin;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class GalleryController extends \Http\Controller {

    private $upload_handler;

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function getJsonView($data, \Request $request)
    {
        $gallery = new \always\Gallery;
        $cmd = $request->shiftCommand();
        if (empty($cmd)) {
            $cmd = 'upload';
        }

        switch ($cmd) {
            case 'upload':
                $gallery->upload($request);
                exit();

            case 'pickdefault':
                $gallery->pickDefault($request);
                exit();
        }
    }

    public function getHtmlView($data, \Request $request)
    {
        $gallery = new \always\Gallery;
        $cmd = $request->shiftCommand();
        if (empty($cmd)) {
            $cmd = 'form';
        }
        switch ($cmd) {
            case 'form':
                $template = $gallery->form($request);
                break;

            case 'upload':
                $gallery->upload($request);
                exit();
        }

        if (!empty(\Session::getInstance()->always_message)) {
            $ses = \Session::getInstance();
            $template->add('message', $ses->always_message);
            unset($ses->always_message);
        }
        return $template;
    }

    public function post(\Request $request)
    {
        $gallery = new \always\Gallery;
        $cmd = $request->shiftCommand();
        switch ($cmd) {
            case 'upload':
                ob_start();
                $gallery->upload($request);
                $result = ob_get_clean();
                $images = $gallery->plugUploads($result);
                echo $images;
                exit();
            case 'caption':
                $gallery->saveCaption($request);
                exit();
        }
    }

    public function delete(\Request $request)
    {
        $gallery = new \always\Gallery;
        $gallery->delete($request);
    }

}

?>
