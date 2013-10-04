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
        $this->first_name->setLimit(50);
        $this->first_name->allowEmpty(false);
        $this->last_name = new \Variable\String(null, 'last_name');
        $this->last_name->setLimit(50);
        $this->last_name->allowEmpty(false);
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


    private function loadUsername()
    {
        $db = \Database::newDB();
        $us = $db->addTable('users');
        $us->addField('username');
        $ap = $db->addTable('always_parents', null, false);
        $db->addConditional($db->createConditional($us->getField('id'),
                        $ap->getField('user_id')));
        $ap->addFieldConditional('id', $this->id);
        $result = $db->selectOneRow();
        if (!isset($result['username'])) {
            throw new \Exception('Could not load user name for parent with id=' . $this->id);
        } else {
            $this->username->set($result['username']);
        }
    }

    public function getUsername()
    {
        if ($this->username->isEmpty()) {
            $this->loadUsername();
        }
        return $this->username->get();
    }

    public function getUserId()
    {
        return $this->user_id->get();
    }

    public function setUserId($id)
    {
        $this->user_id = $id;
    }

    public function getFirstName()
    {
        return $this->first_name->get();
    }

    public function getLastName()
    {
        return $this->last_name->get();
    }

    public function setFirstName($first_name)
    {
        $this->first_name->set($first_name);
    }

    public function setLastName($last_name)
    {
        $this->last_name->set($last_name);
    }

    public function getFullName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }


}

?>
