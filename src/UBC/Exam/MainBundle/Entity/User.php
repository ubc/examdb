<?php

namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Holds information about the user who logged into the system
 * 
 * @ORM\Entity(repositoryClass="UBC\Exam\MainBundle\Entity\UserRepository")
 * @ORM\Table(name="user")
 */
class User implements UserInterface, EquatableInterface, \Serializable
{
    const ROLE_DEFAULT = 'ROLE_USER';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=25, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $firstname;
    
    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $lastname;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true, nullable=true)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $puid;

    /**
     * @var boolean
     * @ORM\Column(name="is_active", type="boolean")
     */
    protected $active;

    /**
     * The salt to use for hashing
     *
     * @var string
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $salt;

    /**
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     * @var \DateTime
     */
    protected $lastLogin;

    /**
     * @ORM\Column(name="roles", type="array")
     * @var array
     */
    protected $roles;

    /**
     *
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $created;
    
    /**
     *
     * @var datetime $updated
     *     
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    protected $modified;

    /**
     * constructor to make default user active.
     */
    public function __construct()
    {
        $this->salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $this->active = false;
        $this->roles = array();
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
        $roles = $this->roles;

        // we need to make sure to have at least one role
        $roles[] = static::ROLE_DEFAULT;

        return array_unique($roles);
    }

    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function setRoles(array $roles)
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
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
            $this->firstname,
            $this->lastname,
            $this->password,
            $this->puid,
            $this->salt,
            $this->active,
        ));
    }

    /**
     * @param String $serialized
     *
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        $data = array_merge($data, array_fill(0, 2, null));

        list(
            $this->id,
            $this->username,
            $this->firstname,
            $this->lastname,
            $this->password,
            $this->puid,
            $this->salt,
            $this->active,
        ) = $data;
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
     * Set puid
     *
     * @param String $puid
     *
     * @return Exam
     */
    public function setPuid($puid)
    {
        $this->puid = $puid;
    
        return $this;
    }
    
    /**
     * Get puid
     *
     * @return string
     */
    public function getPuid()
    {
        return $this->puid;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
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

    /**
     * The equality comparison should neither be done by referential equality
     * nor by comparing identities (i.e. getId() === getId()).
     *
     * However, you do not need to compare every attribute, but only those that
     * are relevant for assessing whether re-authentication is required.
     *
     * Also implementation should consider that $user instance may implement
     * the extended user interface `AdvancedUserInterface`.
     *
     * @param UserInterface $user
     *
     * @return bool
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }


    public function setRoleString($roleString)
    {
       $this->setRoles(explode(',', $roleString));
    }

    public function getRoleString() {
        return join(',', $this->getRoles());
    }
}
