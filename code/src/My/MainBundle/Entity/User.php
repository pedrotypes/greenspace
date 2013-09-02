<?php
namespace My\MainBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends BaseUser
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
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $name;
    public function setName($name) { $this->name = $name; return $this; }
    public function getName() { return $this->name; }



    public function getGravatar($size = 50)
    {
        $gravatarUrl = "https://www.gravatar.com/avatar/"
            . md5($this->email)
            . "?s=" . $size
        ;
        
        return $gravatarUrl;
    }
}