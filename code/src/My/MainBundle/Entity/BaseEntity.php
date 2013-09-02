<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
class BaseEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    public function setId($id) { $this->id = $id; return $this; }
    public function getId() { return $this->id; }

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created;
    public function setCreated($date) { $this->created = $date; return $this; }
    public function getCreated() { return $this->created; }



    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created = $this->created ?: new \DateTime();
    }

    public function __construct()
    {
        $this->created = new \DateTime();
    }
}