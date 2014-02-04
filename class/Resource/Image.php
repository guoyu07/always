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

    public function __construct()
    {
        $this->table = 'always_image';
        $this->path = new \Variable\File(null, 'path');
        $this->caption = new \Variable\TextOnly(null, 'caption');
        $this->profile_id = new \Variable\Integer(0, 'profile_id');
        $this->profile_id->setRange(1);
        $this->parent_id = new \Variable\Integer(0, 'parent_id');
        $this->parent_id->setRange(1);
    }

    public function setPath($path)
    {
        $this->path->set($path);
    }

    public function getPath()
    {
        $this->path->get();
    }

    public function setCaption($caption)
    {
        $this->caption->set($caption);
    }

    public function getCaption()
    {
        $this->caption->get();
    }

    public function setProfileId($id)
    {
        $this->profile_id->set($id);
    }

    public function getProfileId()
    {
        $this->profile_id->get();
    }

    public function setParentId($id)
    {
        $this->parent_id->set($id);
    }

    public function getParentId()
    {
        $this->parent_id->get();
    }

}

?>
