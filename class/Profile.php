<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Profile extends \Resource {

    /**
     * Id of Parent this profile is attached to.
     *
     * @var integer
     */
    protected $parent_id;

    /**
     * Reference to Selected BG
     * @var Variable\String
     */
    protected $bg;

    /**
     * The Profile Picture uploaded
     * @var Variable\String
     */
    protected $profile_pic;

    /**
     * The Story
     * @var Variable\String
     */
    protected $story;

    /**
     * The Summary
     * @var Variable\String
     */
    protected $summary;

    /**
     * Has the story been submitted for approval?
     * @var Variable\Integer
     */
    protected $submitted;

    /**
     * Incremented version of the profile
     *
     * @var integer
     */
    protected $version;

    /**
     * Whether this version has been approved or not
     *
     * @var boolean
     */
    protected $approved;
    protected $table = 'always_profile';

    /**
     *
     * @var string
     */
    protected $first_name;

    /**
     *
     * @var string
     */
    protected $last_name;

    /**
     * @var Variable\String
     */
    protected $class_date;

    /**
     * A version of the first and last name that allows easier pulling of profile
     * information.
     * @var string
     */
    protected $pname;

    public function __construct()
    {
        parent::__construct();
        $this->parent_id = new \Variable\Integer(null, 'parent_id');
        $this->bg = new \Variable\Integer(null, 'bg');
        $this->first_name = new \Variable\String(null, 'first_name');
        $this->first_name->allowEmpty(false);
        $this->last_name = new \Variable\String(null, 'last_name');
        $this->last_name->allowEmpty(false);
        $this->class_date = new \Variable\Integer(null, 'class_date');
        $this->class_date->setRange(1950, date('Y') + 1);
        $this->class_date->setInputType('select');
        $this->profile_pic = new \Variable\File(null, 'profile_pic');
        $this->profile_pic->setInputType('file');
        $this->profile_pic->allowNull();
        $this->story = new \Variable\String(null, 'story');
        $this->story->setInputType('textarea');
        $this->story->setColumnType('Text');
        $this->summary = new \Variable\String(null, 'summary');
        $this->summary->setInputType('textarea');
        $this->summary->setColumnType('Text');
        $this->submitted = new \Variable\Bool(0, 'submitted');
        $this->version = new \Variable\Integer(0, 'version');
        $this->approved = new \Variable\Bool(0, 'approved');
        $this->pname = new \Variable\Attribute(null, 'pname');
    }

    public function getData()
    {
        return $this->getVars();
    }

    public function getParentId()
    {
        return $this->parent_id->get();
    }

    public function setParentId($parent_id)
    {
        $this->parent_id->set((int) $parent_id);
    }

    public function setSummary($summary)
    {
        $this->summary->set($summary);
    }

    public function setStory($story)
    {
        $this->story->set($story);
    }

    public function setProfilePic($pic)
    {
        $this->profile_pic->set($pic);
    }

    public function setSubmitted($submitted)
    {
        $this->submitted->set((bool) $submitted);
    }

    public function setApproved($approved)
    {
        $this->approved->set((bool) $approved);
    }

    public function loadPname()
    {
        $this->pname = preg_replace('/[^\w\-]/', '-',
                $this->first_name . '-' . $this->last_name);
    }

    /**
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name->get();
    }

    /**
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name->get();
    }

    public function setFirstName($name)
    {
        $this->first_name->set(ucwords($name));
    }

    public function setLastName($name)
    {
        $this->last_name->set(ucwords($name));
    }

    public function getFullName()
    {
        return $this->first_name  . ' ' . $this->last_name;
    }

    public function getViewUrl()
    {
        return \Server::getSiteUrl() . "/always/$this->pname";
    }

    public function setClassDate($class_date)
    {
        $this->class_date->set($class_date);
    }

}

?>
