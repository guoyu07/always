<?php

namespace always\Resource;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Image extends \Resource {

    protected $path;
    protected $caption;
    protected $profile_id;
    protected $parent_id;
    protected $main;
    protected $table = 'always_parents';

    public function __construct()
    {
        parent::__construct();
        $this->table = 'always_image';
        $this->path = new \Variable\File(null, 'path');
        $this->caption = new \Variable\TextOnly(null, 'caption');
        $this->profile_id = new \Variable\Integer(0, 'profile_id');
        $this->profile_id->setRange(1);
        $this->parent_id = new \Variable\Integer(0, 'parent_id');
        $this->parent_id->setRange(1);
        $this->main = new \Variable\Bool(false, 'main');
    }

    public function setPath($path)
    {
        $this->path->set($path);
    }

    public function getPath()
    {
        return $this->path->get();
    }

    /**
     * Returns url of image
     * @return type
     */
    public function getUrl()
    {
        return PHPWS_HOME_HTTP . 'images/always/profile' . $this->getProfileId() . '/' . $this->path->get();
    }

    public function setCaption($caption)
    {
        $this->caption->set($caption);
    }

    public function getCaption()
    {
        return $this->caption->get();
    }

    public function setProfileId($id)
    {
        $this->profile_id->set($id);
    }

    public function getProfileId()
    {
        return $this->profile_id->get();
    }

    public function setParentId($id)
    {
        $this->parent_id->set($id);
    }

    public function getParentId()
    {
        return $this->parent_id->get();
    }

    public function setMain($main)
    {
        $this->main->set($main);
    }

    public function getMain()
    {
        return $this->main->get();
    }

}

?>
