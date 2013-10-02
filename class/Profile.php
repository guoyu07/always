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

    public function __construct()
    {
        $this->parent_id = new \Variable\Integer(null, 'parent_id');
        $this->bg = new \Variable\Integer(null, 'bg');
        $this->profile_pic = new \Variable\String(null, 'profile_pic');
        $this->story = new \Variable\String(null, 'story');
        $this->summary = new \Variable\String(null, 'summary');
        $this->story->setColumnType('Text');
        $this->summary->setColumnType('Text');
        $this->submitted = new \Variable\Bool(0, 'submitted');
        $this->version = new \Variable\Integer(0, 'version');
        $this->approved = new \Variable\Bool(0, 'approved');
    }

    public function getData()
    {
        return $this->getVars();
    }

}

?>
