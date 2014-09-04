<?php

namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Holds information about the user who logged into the system
 * 
 * @ORM\Entity(repositoryClass="UBC\Exam\MainBundle\Entity\UserRepository")
 * @ORM\Table(name="user")
 */
class User implements UserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;
    
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $firstname;
    
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $lastname;
    
    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $password;
    
    /**
     * @ORM\Column(type="string", length=60, unique=true, nullable=true)
     */
    private $email;
    
    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;
    
    /**
     *
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;
    
    /**
     *
     * @var datetime $updated
     *     
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $modified;

    /**
     * constructor to make default user active.
     */
    public function __construct()
    {
        $this->isActive = true;
        // may not be needed, see section on salt below
        // $this->salt = md5(uniqid(null, true));
    }
    

    /**
     * returns user's id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     * 
     * @return String
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * sets the user's username
     * 
     * @param string $username
     * 
     * @return \UBC\Exam\MainBundle\Entity\User
     */
    public function setUsername($username)
    {
        $this->username = $username;
        
        return $this;
    }

    /**
     * @inheritDoc
     * 
     * @return null
     */
    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    /**
     * @inheritDoc
     * 
     * @return String
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @inheritDoc
     * 
     * @return array
     */
    public function getRoles()
    {
        return array(
                'ROLE_USER' 
        );
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * @see \Serializable::serialize()
     * 
     * @return String
     */
    public function serialize()
    {
        return serialize( array(
                $this->id,
                $this->username,
                $this->password 
        // see section on salt below
        // $this->salt,
                ));
    }

    /**
     * @param String $serialized
     *
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }
    
    /**
     * Set firstname
     *
     * @param string $firstname
     * 
     * @return Exam
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    
        return $this;
    }
    
    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }
    
    /**
     * Set lastname
     *
     * @param string $lastname
     * 
     * @return Exam
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    
        return $this;
    }
    
    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param String $email
     * 
     * @return Exam
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
    }
    
    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * Get modified
     *
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }
}
