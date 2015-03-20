<?php
namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use ZendSearch\Lucene\Document;
use ZendSearch\Lucene\Document\Field;

/**
 * This class holds the concept of exam (aka file associated with course)
 *
 * @ORM\Entity
 * @ORM\Table(name="exam", indexes={@Index(name="IDX_STATS",   columns={"campus", "faculty"}),
 *                                  @Index(name="IDX_SUBJECT", columns={"subject_code"})})
 * @ORM\Entity(repositoryClass="UBC\Exam\MainBundle\Entity\ExamRepository")
 */
class Exam
{
    const ACCESS_LEVEL_EVERYONE = 1;
    const ACCESS_LEVEL_CWL      = 2;
    const ACCESS_LEVEL_FACULTY  = 3;
    const ACCESS_LEVEL_COURSE   = 4;
    const ACCESS_LEVEL_ME       = 5;
    static public $ACCESS_LEVELS = array(
        self::ACCESS_LEVEL_EVERYONE => 'Everyone',
        self::ACCESS_LEVEL_CWL      => 'People with UBC CWLs',
        self::ACCESS_LEVEL_FACULTY  => 'Students with Courses in This Faculty',
        self::ACCESS_LEVEL_COURSE   => 'Current Course Participants',
        self::ACCESS_LEVEL_ME       => 'Only Me'
    );
    static public $TYPES = array('Actual Assessment' => 'Past Exam', 'Practice Assessment' => 'Practice Exam', 'Other Material' => 'Other Exam Prep Material');
    static public $TERMS = array('w' => 'W', 'w1' => 'W1', 'w2' => 'W2', 's' => 'S', 's1' => 'S1', 's2' => 'S2', 'sa' => 'SA', 'sb' => 'SB', 'sc' => 'SC', 'sd' => 'SD');

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=5)
     * @Assert\NotBlank(message="Please choose a campus")
     */
    protected $campus;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(message="Please choose a faculty")
     */
    protected $faculty;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(message="Please include a department or 'n/a'")
     */
    protected $dept;


    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(message="Please provide a subject code")
     */
    protected $subject_code;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(message="Please provide a year")
     */
    protected $year;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank(message="Please provide a term")
     */
    protected $term;

    /**
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank(message="Please provide a document type")
     */
    protected $type;

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
     * @Assert\NotBlank(message="Please attribute an owner")
     */
    protected $legal_content_owner;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message="Please designate an uploader")
     */
    protected $legal_uploader;

    /**
     * @ORM\Column(type="date")
     * @Assert\NotBlank(message="Please provide the date")
     */
    protected $legal_date;

    /**
     * @ORM\Column(type="boolean")
     * @Assert\NotBlank(message="Please agree to the terms")
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
     * @Assert\NotBlank(message="Please choose an access level")
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

    /**
     * @var int $downloads
     * @ORM\Column(type="integer")
     * @Assert\NotBlank(message="Please choose an access level")
     */
    private $downloads = 0;

    private $temp;

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     *
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

    public function preUpload()
    {
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename.'.'.$this->getFile()->guessExtension();
        }
    }

    public function upload($uploadDir)
    {
        if (null === $this->getFile()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->getFile()->move($uploadDir, $this->path);

        // check if we have an old image
        if (isset($this->temp)) {
            // delete the old image
            unlink($uploadDir.'/'.$this->temp);
            // clear the temp image path
            $this->temp = null;
        }
        $this->file = null;
    }

    public function removeUpload($uploadDir)
    {
        if ($file = $this->getAbsolutePath($uploadDir)) {
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
    public function getAbsolutePath($uploadDir)
    {
        return null === $this->path ? null : $uploadDir.'/'.$this->path;
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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getCampus()
    {
        return $this->campus;
    }

    /**
     * @param mixed $campus
     */
    public function setCampus($campus)
    {
        $this->campus = $campus;
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
     * @param string
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
     * Set type
     *
     * @param string $type
     *
     * @return Exam
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * gets user-friendly version of type
     *
     * @return String
     */
    public function getTypeString()
    {
        return self::$TYPES[$this->type];
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
     * @return string
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
     * @return string
     */
    public function getLegalUploader()
    {
        return $this->legal_uploader;
    }

    /**
     * sets legal_date
     *
     * @param string $legal_date
     * @return Exam
     * @throws \Exception
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
     *
     * @return String
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

    /**
     * @return int
     */
    public function getDownloads()
    {
        return $this->downloads;
    }

    /**
     * @param int $downloads
     */
    public function setDownloads($downloads)
    {
        $this->downloads = $downloads;
    }

    /**
     * Return a user friendly filename for downloading
     * @return string
     */
    public function getUserFilename() {
        $ext = pathinfo($this->path, PATHINFO_EXTENSION);
        return str_replace(' ', '_',
            join('_', array(
                $this->subject_code,
                $this->year,
                $this->term,
                $this->legal_content_owner,
                $this->getTypeString()
            ))
        ) . '.' . $ext;
    }

    public function increaseDownloads()
    {
        $this->downloads++;
    }

    public function index($indexer, $commit = true, $optimize = true) {
        $document = new Document();
        $document->addField(Field::keyword('pk', $this->getId()));
        $document->addField(Field::Text('course', $this->getSubjectcode()));
        $document->addField(Field::Text('cross-listed', str_replace(array(';',',','|'), ' ', $this->getCrossListed())));
        $document->addField(Field::Text('instructor', $this->getLegalContentOwner()));
        $document->addField(Field::Unstored('comments', $this->getComments()));

        $indexer->addDocument($document);

        if ($commit) {
            $indexer->commit();
        }

        if ($optimize) {
            $indexer->optimize();
        }
    }

    /**
     * Delete index for the current exam entity
     *
     * @param \ZendSearch\Lucene\SearchIndexInterface $indexer
     */
    public function deleteIndex($indexer) {
        $hits = $indexer->find('pk:'.$this->getId());
        // there should be only one, but just in case
        foreach($hits as $hit) {
            $indexer->delete($hit);
        }
        $indexer->commit();
    }
}
