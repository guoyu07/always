<?php

namespace always;

/**
 *
 * @author Matthew McNaney <mcnaney at gmail dot com>
 * @license http://opensource.org/licenses/lgpl-3.0.html
 */
class Parents extends \Resource {

    /**
     * Id for each Parent
     * @var Variable\Integer
     */
    protected $id;

    /**
     * Id of user account tied to Parent
     * @var integer
     */
    protected $user_id;

    /**
     * Username of users table. Also the email address.
     * @var string
     */
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
    protected $student_fname;

    /**
     * @var Variable\String
     */
    protected $student_lname;

    /**
     * @var Variable\String
     */
    protected $class_date;

    /**
     * The Database table
     * @var Variable\String
     */
    protected $table = 'always_parents';

    public function __construct()
    {
        parent::__construct();
        $this->id = new \Variable\Integer(null, 'id');
        $this->user_id = new \Variable\Integer(null, 'user_id');
        $this->first_name = new \Variable\String(null, 'first_name');
        $this->first_name->allowEmpty(false);
        $this->last_name = new \Variable\String(null, 'last_name');
        $this->last_name->allowEmpty(false);
        $this->student_fname = new \Variable\String(null, 'student_fname');
        $this->student_fname->allowEmpty(false);
        $this->student_lname = new \Variable\String(null, 'student_lname');
        $this->student_lname->allowEmpty(false);
        $this->class_date = new \Variable\Integer(null, 'class_date');
        $this->class_date->setRange(1950, date('Y') + 1);
        $this->class_date->setInputType('select');
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
