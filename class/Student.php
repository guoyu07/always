<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Student extends \Resource {

    /**
     * Id for each Student
     * @var Variable\Integer
     */
    protected $id;

    /**
     * Id of user account tied to student
     * @var integer
     */
    protected $user_id;

    private $username;

    /**
     * @var Variable\String
     */
    protected $first_name;

    /**
     * @var Variable\String
     */
    protected $last_name;

    /**
     * @var Variable\String
     */
    protected $class_date;

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
     * The 'Live' Story
     * @var Variable\String
     */
    protected $live_story;

    /**
     * The 'Live' Summary
     * @var Variable\String
     */
    protected $live_summary;

    /**
     * The 'Live' Profile Pic
     * @var Variable\String
     */
    protected $live_profile_pic;

    /**
     * The Database table
     * @var Variable\String
     */
    protected $table = 'always_student';
    public function __construct()
    {
        parent::__construct();
        $this->id = new \Variable\Integer(null, 'id');
        $this->first_name = new \Variable\String(null, 'first_name');
        $this->first_name->allowEmpty(false);
        $this->last_name = new \Variable\String(null, 'last_name');
        $this->last_name->allowEmpty(false);
        $this->class_date = new \Variable\Integer(null, 'class_date');
        $this->class_date->setRange(1950, date('Y') + 1);
        $this->class_date->setInputType('select');

        $this->bg = new \Variable\String(null, 'bg');
        $this->profile_pic = new \Variable\String(null, 'profile_pic');
        $this->story = new \Variable\String(null, 'story');
        $this->summary = new \Variable\String(null, 'summary');
        $this->submitted = new \Variable\Integer(0, 'submitted');
        $this->username = new \Variable\Email(null, 'username');
    }

    public function __set($name, $value)
    {
        $this->$name->set($value);
    }

    public function __get($name)
    {
        return $this->$name->get();
    }

    public function isSubmitted()
    {
        return (bool) $this->submitted->get();
    }
}

?>
