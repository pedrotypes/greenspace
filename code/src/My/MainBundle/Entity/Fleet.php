<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Player;


/**
 * @ORM\Entity
 * @ORM\Table(name="fleets")
 */
class Fleet extends BaseEntity
{
    const DEFAULT_RANGE = 100;
    
    /**
     * @ORM\ManyToOne(targetEntity="Base", inversedBy="fleets")
     */
    private $base;
    public function setBase(Base $base) { $this->base = $base; return $this; }
    public function getBase() { return $this->base; }

    /**
     * @ORM\ManyToOne(targetEntity="Base", inversedBy="outbound")
     */
    private $origin;
    public function setOrigin(Base $origin) { $this->origin = $origin; return $this; }
    public function getOrigin() { return $this->origin; }
    public function clearOrigin() { $this->origin = null; return $this; }

    /**
     * @ORM\ManyToOne(targetEntity="Base", inversedBy="inbound")
     */
    private $destination;
    public function setDestination(Base $destination) { $this->destination = $destination; return $this; }
    public function getDestination() { return $this->destination; }
    public function clearDestination() { $this->destination = null; return $this; }

    /**
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="fleets")
     */
    private $player;
    public function setPlayer(Player $player) { $this->player = $player; return $this; }
    public function getPlayer() { return $this->player; }

    /**
     * @ORM\Column(type="integer")
     */
    private $power = 0;
    public function setPower($power) { $this->power = $power; return $this; }
    public function getPower() { return $this->power; }
}