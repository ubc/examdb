<?php 
namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class holds the 4 letters representing course codes in UBC
 *
 * @ORM\Entity
 * @ORM\Table(name="subjectfaculty")
 */
class SubjectFaculty
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO") 
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $code;
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $title;
    
    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $faculty;
    
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $code
     * 
     * @return SubjectFaculty
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }
    
    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * Set faculty
     *
     * @param string $faculty
     *
     * @return SubjectFaculty
     */
    public function setFaculty($faculty)
    {
        $this->faculty = $faculty;
    
        return $this;
    }
    
    /**
     * Get faculty
     *
     * @return string
     */
    public function getFaculty()
    {
        return $this->faculty;
    }
    
    /**
     * Set title
     *
     * @param string $title
     *
     * @return SubjectFaculty
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }
    
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
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