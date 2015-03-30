<?php

namespace UBC\Exam\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sessions
 *
 * @ORM\Table(name="sessions")
 * @ORM\Entity
 */
class Sessions
{
    /**
     * @var string
     *
     * @ORM\Column(name="sess_data", type="blob", length=65535, nullable=false)
     */
    private $sessData;

    /**
     * @var integer
     *
     * @ORM\Column(name="sess_time", type="integer", nullable=false)
     */
    private $sessTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="sess_lifetime", type="integer", nullable=false)
     */
    private $sessLifetime;

    /**
     * @var binary
     *
     * @ORM\Column(name="sess_id", type="binary")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $sessId;



    /**
     * Set sessData
     *
     * @param string $sessData
     * @return Sessions
     */
    public function setSessData($sessData)
    {
        $this->sessData = $sessData;

        return $this;
    }

    /**
     * Get sessData
     *
     * @return string 
     */
    public function getSessData()
    {
        return $this->sessData;
    }

    /**
     * Set sessTime
     *
     * @param integer $sessTime
     * @return Sessions
     */
    public function setSessTime($sessTime)
    {
        $this->sessTime = $sessTime;

        return $this;
    }

    /**
     * Get sessTime
     *
     * @return integer 
     */
    public function getSessTime()
    {
        return $this->sessTime;
    }

    /**
     * Set sessLifetime
     *
     * @param integer $sessLifetime
     * @return Sessions
     */
    public function setSessLifetime($sessLifetime)
    {
        $this->sessLifetime = $sessLifetime;

        return $this;
    }

    /**
     * Get sessLifetime
     *
     * @return integer 
     */
    public function getSessLifetime()
    {
        return $this->sessLifetime;
    }

    /**
     * Get sessId
     *
     * @return binary 
     */
    public function getSessId()
    {
        return $this->sessId;
    }
}
