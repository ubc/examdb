<?php
namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class holds the concept of exam (aka file associated with course)
 * 
 * @ORM\Entity
 * @ORM\Table(name="exam")
 * @ORM\HasLifecycleCallbacks
 */
class Exam
{
    static public $ACCESS_LEVELS = array('1' => 'Public', '2' => 'CWL', '3' => 'Faculty', '4' => 'Course');
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO") 
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $faculty;
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    protected $dept;
    
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $subject_code;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $year;
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $term;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comments;
    
    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $cross_listed;
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $legal_content_owner;
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $legal_uploader;
    
    /**
     * @ORM\Column(type="date")
     */
    protected $legal_date;
    
    /**
     * @ORM\Column(type="boolean")
     */
    protected $legal_agreed;

    /**
     * 
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="uploaded_by", referencedColumnName="id")
     */
    protected $uploaded_by;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $access_level;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $path;

    /**
     * file
     * @var unknown
     */
    private $file;
    
    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;
    
    /**
     * @var datetime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $modified;
    
    private $temp;
    
    /**
     * Sets file.
     *
     * @param UploadedFile $file
     * @return void
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
        
        // check if we have an old image path
        if (isset($this->path)) {
            // store the old name to delete after the update
            $this->temp = $this->path;
            $this->path = null;
        } else {
            $this->path = 'initial';
        }
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    { 
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename.'.'.$this->getFile()->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->getFile()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getFile()->move($this->getUploadRootDir(), $this->path);

        // check if we have an old image
        if (isset($this->temp)) {
            // delete the old image
            unlink($this->getUploadRootDir().'/'.$this->temp);
            // clear the temp image path
            $this->temp = null;
        }
        $this->file = null;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }   

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * returns null or absolute system path to file
     * @return Ambigous <NULL, string>
     */
    public function getAbsolutePath()
    {
        return null === $this->path ? null : $this->getUploadRootDir().'/'.$this->path;
    }

    /**
     * returns either null or relative web path to file
     * @return mixed <NULL, string>
     */
    public function getWebPath()
    {
        return null === $this->path ? null : $this->getUploadDir().'/'.$this->path;
    }

    /**
     * Please see http://symfony.com/doc/2.3/cookbook/doctrine/file_uploads.html#using-lifecycle-callbacks to remove 
     * hard coded __DIR__
     * @return string
     */
    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    /**
     * returns upload directory
     * 
     * @return string
     */
    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/documents';
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
     * Set faculty
     *
     * @param string $faculty
     * 
     * @return Exam
     */
    public function setFaculty($faculty)
    {
    	$this->faculty = $faculty;
    
    	return $this;
    }

    /**
     * Get dept
     *
     * @return string
     */
    public function getFaculty()
    {
    	return $this->faculty;
    }

    /**
     * Set dept
     *
     * @param string $dept
     * 
     * @return Exam
     */
    public function setDept($dept)
    {
        $this->dept = $dept;
    
        return $this;
    }

    /**
     * Get dept
     *
     * @return string 
     */
    public function getDept()
    {
        return $this->dept;
    }

    /**
     * Set subject_code
     *
     * @param string $number
     * 
     * @return Exam
     */
    public function setSubjectcode($subject_code)
    {
        $this->subject_code = $subject_code;
    
        return $this;
    }

    /**
     * Get subject_code (aka APSC, ADHE, etc)
     *
     * @return string 
     */
    public function getSubjectcode()
    {
        return $this->subject_code;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
    	return $this->path;
    }

    /**
     * Set year
     *
     * @param integer $year
     * 
     * @return Exam
     */
    public function setYear($year)
    {
        $this->year = $year;
    
        return $this;
    }

    /**
     * Get year
     *
     * @return integer 
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set term
     *
     * @param string $term
     * 
     * @return Exam
     */
    public function setTerm($term)
    {
        $this->term = $term;
    
        return $this;
    }

    /**
     * Get term
     *
     * @return string 
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * Set comments
     *
     * @param string $comments
     * 
     * @return Exam
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    
        return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Get cross_listed
     *
     * @return string 
     */
    public function getCrossListed()
    {
        return $this->cross_listed;
    }

    /**
     * Set cross_listed
     *
     * @param string $cross_listed
     * 
     * @return Exam
     */
    public function setCrossListed($cross_listed)
    {
    	$this->cross_listed = $cross_listed;

    	return $this;
    }

    /**
     * set legal_content_owner
     * 
     * @param string $legal_content_owner
     * 
     * @return \UBC\Exam\MainBundle\Entity\Exam
     */
    public function setLegalContentOwner($legal_content_owner) 
    {
    	$this->legal_content_owner = $legal_content_owner;

    	return $this;
    }

    /**
     * gets legal_content_owner
     * 
     * return string
     */
    public function getLegalContentOwner() 
    {
    	return $this->legal_content_owner;
    }

    /**
     * sets legal_uploader
     * 
     * @param string $legal_uploader
     * 
     * @return \UBC\Exam\MainBundle\Entity\Exam
     */
    public function setLegalUploader($legal_uploader) 
    {
    	$this->legal_uploader = $legal_uploader;

    	return $this;
    }
    
    /**
     * gets legal_uploader
     * 
     * return string
     */
    public function getLegalUploader() 
    {
    	return $this->legal_uploader;
    }
    
    /**
     * sets legal_date
     * 
     * @param string $legal_date
     * 
     * @return \UBC\Exam\MainBundle\Entity\Exam
     */
    public function setLegalDate($legal_date) 
    {
    	if (is_string($legal_date)) {
    		$this->legal_date = new \DateTime($legal_date);
    	} else if (is_object($legal_date) && get_class($legal_date) == 'DateTime') {
    		$this->legal_date = $legal_date;
    	} else {
    		throw new \Exception();
    	}
    	
    	return $this;
    }

    /**
     * get legal date
     * 
     * @return \DateTime
     */
    public function getLegalDate()
    {
    	return $this->legal_date;
    }

    /**
     * set legal agreed checkbox result
     * 
     * @param bool $legal_agreed
     * 
     * @return \UBC\Exam\MainBundle\Entity\Exam
     */
    public function setLegalAgreed($legal_agreed)
    {
    	$this->legal_agreed = $legal_agreed;
    	
    	return $this;
    }

    /**
     * get legal chekbox result
     * 
     * @return boolean
     */
    public function getLegalAgreed()
    {
    	return $this->legal_agreed;
    }

    /**
     * Set uploaded_by
     *
     * @param integer $uploadedBy
     * 
     * @return Exam
     */
    public function setUploadedBy($uploadedBy)
    {
        $this->uploaded_by = $uploadedBy;
    
        return $this;
    }

    /**
     * Get uploaded_by
     *
     * @return integer 
     */
    public function getUploadedBy()
    {
        return $this->uploaded_by;
    }

    /**
     * Set access_level
     *
     * @param integer $accessLevel
     * 
     * @return Exam
     */
    public function setAccessLevel($accessLevel)
    {
        $this->access_level = $accessLevel;
    
        return $this;
    }

    /**
     * Get access_level
     *
     * @return integer 
     */
    public function getAccessLevel()
    {
        return $this->access_level;
    }
    
    /**
     * converts integer representation of access level to string
     */
    public function getAccessLevelString()
    {
        return self::$ACCESS_LEVELS[$this->access_level];
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
