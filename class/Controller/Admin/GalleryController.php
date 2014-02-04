<?php

namespace always\Controller\Admin;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class GalleryController extends \Http\Controller {

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    public function delete(\Request $request)
    {
        $this->upload($request);
        exit();
    }

    public function getJsonView($data, \Request $request)
    {
        $this->upload($request);
        exit();
    }

    public function getHtmlView($data, \Request $request)
    {
        $cmd = $request->shiftCommand();
        if (empty($cmd)) {
            $cmd = 'form';
        }
        switch ($cmd) {
            case 'form':
                $template = $this->form($request);
                break;

            case 'upload':
                $this->upload($request);
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
        $cmd = $request->shiftCommand();
        switch ($cmd) {
            case 'upload':
                $this->upload($request);
                exit();
        }
    }

    private function form(\Request $request)
    {
        $profile_id = $request->getVar('profile_id');
        $profile = \always\Factory\ProfileFactory::getProfileById($profile_id);
        javascript('jquery');
        javascript('jquery_ui');
        $local_url = \Server::getSiteUrl();
        $always_url = $local_url . 'mod/always/javascript/';
        $upload_url = $local_url . 'always/admin/gallery/upload/?profile_id=' . $profile_id;

        //$upload_url = '//localhost/phpwebsite/always/admin/gallery/upload/';
        $header = <<<EOF
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<link rel="stylesheet" href="{$local_url}javascript/jquery_ui/css/smoothness/jquery-ui-1.10.3.custom.min.css" id="theme">
<link rel="stylesheet" href="{$always_url}gallery/css/blueimp-gallery.min.css">
<link rel="stylesheet" href="{$always_url}jquery_upload/css/jquery.fileupload.css">
<script src="{$always_url}jquery_upload/js/tmpl.min.js"></script>
<script src="{$always_url}jquery_upload/js/load-image.min.js"></script>
<script src="{$always_url}jquery_upload/js/canvas-to-blob.min.js"></script>
<script src="{$always_url}gallery/js/jquery.blueimp-gallery.min.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.iframe-transport.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.fileupload.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.fileupload-process.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.fileupload-image.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.fileupload-validate.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.fileupload-ui.js"></script>
<script src="{$always_url}jquery_upload/js/jquery.fileupload-jquery-ui.js"></script>
<script>var upload_url = '$upload_url';var profile_id = '$profile_id';</script>
<script src="{$always_url}jquery_upload/js/main.js"></script>

<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="{$always_url}jquery_upload/js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
EOF;
        \Layout::addJSHeader($header, 'jquery_upload');

        $template = new \Template;
        $name = '<a href="' . $profile->getViewUrl() . '">' . $profile->getFullName() . '</a>';
        $template->add('name', $name);
        $template->add('profile_id', $profile->getId());
        $template->setModuleTemplate('always', 'Admin/Gallery/form.html');

        return $template;
    }

    private function upload(\Request $request)
    {
        require_once PHPWS_SOURCE_DIR . 'mod/always/class/UploadHandler.php';

        if (!$request->isVar('profile_id')) {
            echo json_encode(array('error'=>'No profile selected'));
        }
        $profile_id = $request->getVar('profile_id');

        $profile = \always\Factory\ProfileFactory::getProfileById($profile_id);
        $site_url = \Server::getSiteUrl();

        $upload_dir = $profile->getImageDirectory();
        $upload_url = $profile->getImageUrl();

        $options = array(
            'script_url' => $site_url . 'always/admin/gallery/upload/',
            'upload_dir' => $upload_dir,
            'upload_url' => $upload_url
        );
        $upload_handler = new \UploadHandler($options);
    }

}

?>
