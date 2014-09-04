<?php 
namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This class holds the 4 letters representing course codes in UBC
 *
 * @ORM\Entity
 * @ORM\Table(name="subjectcode")
 */
class SubjectCode
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
     * @return SubjectCode
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
    public function getName()
    {
        return $this->code;
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