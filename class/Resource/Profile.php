<?php

namespace always\Resource;

require_once PHPWS_SOURCE_DIR . 'mod/always/inc/defines.php';

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Profile extends \Resource {

    /**
     * Id of Parent this profile is attached to.
     *
     * @var \Variable\Integer
     */
    protected $parent_id;

    /**
     * Original id of first profile in series
     * @var \Variable\Integer
     */
    protected $original_id;

    /**
     * A version of the first and last name that allows easier pulling of profile
     * information.
     * @var \Variable\Attribute
     */
    protected $pname;

    /**
     *
     * @var \Variable\TextOnly
     */
    protected $first_name;

    /**
     *
     * @var \Variable\TextOnly
     */
    protected $last_name;

    /**
     * Year of graduation
     * @var \Variable\Integer
     */
    protected $class_date;

    /**
     * The Profile Picture uploaded, this will be replaced eventually once
     * multiple pictures can be uploaded.
     * @var Variable\File
     */
    protected $profile_pic;

    /**
     * A short summary of the student. No html.
     * @var Variable\String
     */
    protected $summary;

    /**
     * The full html text describing the student
     * @var Variable\TextOnly
     */
    protected $story;

    /**
     * Reference to Selected BG. This functionality is not yet in program.
     * @var Variable\Integer
     */
    protected $bg;

    /**
     * If true, the parent has submitted an updated profile for approval.
     * @var Variable\Integer
     */
    protected $submitted;

    /**
     * Incremented version of the profile. Updated after each approved version.
     *
     * @var \Variable\Integer
     */
    protected $version;

    /**
     * Whether this version has been approved or not. Can only be approved by
     * an admin.
     *
     * @var \Variable\Bool
     */
    protected $approved;

    /**
     * The name of the last admin to update the profile.
     * @var \Variable\TextOnly
     */
    protected $last_editor;

    /**
     * Date and time when profile was last updated
     * @var \Variable\DateTime
     */
    protected $last_updated;

    protected $table = 'always_profile';

    public function __construct()
    {
        parent::__construct();
        $this->parent_id = new \Variable\Integer(null, 'parent_id');
        $this->original_id = new \Variable\Integer(null, 'original_id');
        $this->pname = new \Variable\Attribute(null, 'pname');
        $this->first_name = new \Variable\TextOnly(null, 'first_name');
        $this->first_name->allowEmpty(false);
        $this->first_name->setLimit(50);
        $this->last_name = new \Variable\TextOnly(null, 'last_name');
        $this->last_name->allowEmpty(false);
        $this->last_name->setLimit(50);
        $this->class_date = new \Variable\Integer(null, 'class_date');
        $this->class_date->setRange(CLASS_DATE_LOW_RANGE, date('Y') + 4);
        $this->class_date->setInputType('select');
        $this->profile_pic = new \Variable\File(null, 'profile_pic');
        $this->profile_pic->setInputType('file');
        $this->profile_pic->allowNull();
        $this->summary = new \Variable\TextOnly(null, 'summary');
        $this->summary->setInputType('textarea');
        $this->summary->setColumnType('Text');
        $this->story = new \Variable\String(null, 'story');
        $this->story->setInputType('textarea');
        $this->story->setColumnType('Text');
        $this->bg = new \Variable\Integer(null, 'bg');
        $this->submitted = new \Variable\Bool(0, 'submitted');
        $this->version = new \Variable\Integer(0, 'version');
        $this->approved = new \Variable\Bool(0, 'approved');
        $this->last_editor = new \Variable\TextOnly(null, 'last_editor');
        $this->last_editor->setLimit(100);
        $this->last_editor->allowNull(true);
        $this->last_updated = new \Variable\Datetime(0, 'last_updated');
    }

    public function getClassDate()
    {
        return $this->class_date->get();
    }

    public function getData()
    {
        return $this->getVars();
    }

    public function getParentId()
    {
        return $this->parent_id->get();
    }

    public function getPname()
    {
        return $this->pname;
    }

    public function getProfilePic()
    {
        if ($this->profile_pic->isEmpty()) {
            return null;
        } else {
            $img = new \Tag\Image($this->profile_pic->get());
            return $img;
        }
    }

    public function getSummary()
    {
        return $this->summary;
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

    public function getOriginalId()
    {
        return $this->original_id->get();
    }

    public function setFirstName($name)
    {
        $this->first_name->set(ucwords(trim($name)));
    }

    public function setLastEditor($editor)
    {
        $this->last_editor->set($editor);
    }

    public function stampLastUpdated()
    {
        $this->last_updated->stamp();
    }

    public function setLastName($name)
    {
        $this->last_name->set(ucwords(trim($name)));
    }

    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getViewUrl()
    {
        return \Server::getSiteUrl() . "always/$this->pname";
    }

    public function setClassDate($class_date)
    {
        $this->class_date->set($class_date);
    }

    public function copyOriginalId()
    {
        $this->original_id->set($this->id->get());
    }

    /**
     * Returns true if this profile has been approved by an admin.
     * @return boolean
     */
    public function isApproved()
    {
        return $this->approved->get();
    }

    /**
     * Returns true if this profile has been submitted by parent
     * @return boolean
     */
    public function isSubmitted()
    {
        return $this->submitted->get();
    }

    /**
     * Returns true if this profile is first one every created.
     * @return boolean
     */
    public function isFirst()
    {
        return $this->version->get() == 0;
    }

    public function cloneProfile()
    {
        $this->id->set(0);
        $this->submitted->set(false);
        $this->approved->set(false);
        $this->approved->set(0);
        $this->last_editor->set(null);
        $this->last_updated->set(0);
        $this->version->increase();
    }

}

?>
