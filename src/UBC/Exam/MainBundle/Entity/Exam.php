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
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO") 
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=4)
     */
    protected $dept;
    
    /**
     * @ORM\Column(type="string", length=4)
     */
    protected $number;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $year;
    
    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $term;
    
    /**
     * @ORM\Column(type="text")
     */
    protected $comments;
    
    /**
     * 
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $uploaded_by;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $access_level;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

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
    
    /**
     * file
     * @var unknown
     */
    private $file;
    
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
        // the file property can be empty if the field is not required
        if (null === $this->getFile()) {
            return;
        }
    
        // use the original file name here but you should
        // sanitize it at least to avoid any security issues
    
        // move takes the target directory and then the
        // target filename to move to
        $this->getFile()->move(
                $this->getUploadRootDir(),
                $this->getFile()->getClientOriginalName()
        );
    
        // set the path property to the filename where you've saved the file
        $this->path = $this->getFile()->getClientOriginalName();
    
        // clean up the file property as you won't need it anymore
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
    
    public function getAbsolutePath()
    {
        return null === $this->path ? null : $this->getUploadRootDir().'/'.$this->path;
    }
    
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
     * Set dept
     *
     * @param string $dept
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
     * Set number
     *
     * @param string $number
     * @return Exam
     */
    public function setNumber($number)
    {
        $this->number = $number;
    
        return $this;
    }

    /**
     * Get number
     *
     * @return string 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set year
     *
     * @param integer $year
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
     * Set uploaded_by
     *
     * @param integer $uploadedBy
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
}
