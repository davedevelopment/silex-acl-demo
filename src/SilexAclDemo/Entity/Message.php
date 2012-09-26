<?php

namespace SilexAclDemo\Entity;

use DateTime;

/**
 * @Entity
 */
class Message
{
    /**
     * @Id @Column(type="integer") @GeneratedValue 
     */
    protected $id;

    /**
     * @Column(type="string",length=65536)
     */
    protected $content;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="string", length=255)
     */
    protected $username;

    public function __construct($content, $username)
    {
        $this->content = $content;
        $this->username = $username;
        $this->created = new DateTime;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Message
     */
    public function setContent($content)
    {
        $this->content = $content;
    
        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return Message
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return Message
     */
    public function setUsername($username)
    {
        $this->username = $username;
    
        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }
}