<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Gallery {

    private $profile;
    private $original_id;
    private $profile_id;

    public function form(\Request $request)
    {
        $this->loadProfile($request);
        javascript('jquery');
        javascript('jquery_ui');
        $source_http = PHPWS_SOURCE_HTTP;
        $local_url = \Server::getSiteUrl();
        $always_url = PHPWS_SOURCE_HTTP . 'mod/always/javascript/';
        if (\Current_User::allow('always')) {
            $command_dir = $local_url . 'always/admin/';
            $upload_url = $local_url . 'always/admin/gallery/upload/?profile_id=' . $this->original_id;
        } else {
            $command_dir = $local_url . 'always/parent/';
            $upload_url = $local_url . 'always/parent/gallery/upload/?profile_id=' . $this->original_id;
        }

        $header = <<<EOF
<!--[if IE]>
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<![endif]-->
<link rel="stylesheet" href="{$source_http}javascript/jquery_ui/css/smoothness/jquery-ui-1.10.3.custom.min.css" id="theme">
<link rel="stylesheet" href="{$always_url}gallery/css/blueimp-gallery.min.css">
<link rel="stylesheet" href="{$always_url}jquery_upload/css/jquery.fileupload.css">
<style>j
.fileupload-progress {
	margin: 10px 0;
}
.fileupload-progress .progress-extended {
	margin-top: 5px;
}
</style>
<script>var command_dir = '$command_dir';</script>
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
<script>var upload_url = '$upload_url';var profile_id = '$this->original_id';</script>
<script src="{$always_url}jquery_upload/js/always.js"></script>

<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->
<!--[if (gte IE 8)&(lt IE 10)]>
<script src="{$always_url}jquery_upload/js/cors/jquery.xdr-transport.js"></script>
<![endif]-->
EOF;
        \Layout::addJSHeader($header, 'jquery_upload');

        $template = new \Template;
        $name = '<a href="' . $this->profile->getViewUrl() . '">' . $this->profile->getFullName() . '</a>';
        $template->add('name', $name);
        $template->add('original_id', $this->profile->getOriginalId());
        $template->setModuleTemplate('always', 'Admin/Gallery/form.html');

        return $template;
    }

    private function loadProfile(\Request $request)
    {
        if (!$request->isVar('profile_id')) {
            echo json_encode(array('error' => 'No profile selected'));
        }
        $this->profile_id = $request->getVar('profile_id');
        $this->profile = \always\Factory\ProfileFactory::getProfileById($this->profile_id);
        $this->original_id = $this->profile->getOriginalId();
    }

    public function upload(\Request $request)
    {
        require_once PHPWS_SOURCE_DIR . 'mod/always/class/UploadHandler.php';

        $site_url = \Server::getSiteUrl();
        $this->loadProfile($request);
        $upload_dir = $this->profile->getImageDirectory();
        $upload_url = $this->profile->getImageUrl();

        if (\Current_User::allow('always')) {
            $script_url = $site_url . 'always/admin/gallery/upload/';
        } else {
            $script_url = $site_url . 'always/parent/gallery/upload/';
        }

        $options = array(
            'script_url' => $script_url,
            'upload_dir' => $upload_dir,
            'upload_url' => $upload_url
        );

        $this->upload_handler = new \UploadHandler($options);
    }

    public function pickDefault($request)
    {
        if (!$request->isVar('image_id')) {
            throw new \Exception('Missing image id');
        }
        $db = \Database::newDB();
        $t1 = $db->addTable('always_image');
        $t1->addValue('main', 0);
        $db->update();

        $image_id = $request->getVar('image_id');
        $image = \always\Factory\ImageFactory::getImageById($image_id);
        $image->setMain(true);
        \ResourceFactory::saveResource($image);

        $db = \Database::newDB();
        $t1 = $db->addTable('always_profile');
        $t1->addFieldConditional('original_id', $image->getProfileId());
        $t1->addValue('profile_pic', $image->getUrl());
        $db->update();
    }

    public function plugUploads($file_string)
    {
        $files = json_decode($file_string);
        if (isset($GLOBALS['blueimp_uploads'])) {
            foreach ($GLOBALS['blueimp_uploads'] as $img_path) {
                $values[] = array('path' => $img_path, 'profile_id' => $this->profile->getOriginalId(), 'parent_id' => $this->profile->getParentId());
            }
            $db = \Database::newDB();
            $tbl = $db->addTable('always_image');
            $tbl->addValueArray($values);
            $tbl->insert();
        }
        //pick up here with debugging
        foreach ($files->files as $img) {
            \UploadHandler::fillInFile($img);
        }

        return json_encode($files);
    }

    public function saveCaption($request)
    {
        if (!$request->isVar('caption')) {
            throw new \Exception('Caption variable is not set.');
        }
        $image = \always\Factory\ImageFactory::getImageById($request->getVar('image_id'));
        $image->setCaption($request->getVar('caption'));
        \ResourceFactory::saveResource($image);
    }

    public function delete(\Request $request)
    {
        $this->loadProfile($request);
        $this->upload($request);

        if (isset($GLOBALS['blueimp_delete'])) {
            foreach ($GLOBALS['blueimp_delete'] as $img_path) {
                $db = \Database::newDB();
                $tbl = $db->addTable('always_image');
                $tbl->addFieldConditional('path', $img_path);
                $tbl->addFieldConditional('profile_id', $this->profile_id);
                $db->delete();
                $db->clearConditional();
            }
        }
        exit();
    }

}

?>
