<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Map;
use My\MainBundle\Entity\Player;


/**
 * @ORM\Entity(repositoryClass="My\MainBundle\Entity\BaseRepository")
 * @ORM\Table(name="bases")
 */
class Base extends BaseEntity
{
    const MIN_DEFAULT_RESOURCES = 5;
    const MAX_DEFAULT_RESOURCES = 10;


    /**
     * @ORM\ManyToOne(targetEntity="Map", inversedBy="bases")
     */
    private $map;
    public function setMap(Map $map) { $this->map = $map; return $this; }
    public function getMap() { return $this->map; }

    /**
     * @ORM\Column(type="integer")
     */
    private $x;
    public function setX($x) { $this->x = $x; return $this; }
    public function getX() { return $this->x; }

    /**
     * @ORM\Column(type="integer")
     */
    private $y;
    public function setY($y) { $this->y = $y; return $this; }
    public function getY() { return $this->y; }    

    /**
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="bases")
     */
    private $player;
    public function setPlayer(Player $player) { $this->player = $player; return $this; }
    public function getPlayer() { return $this->player; }

    /**
     * @ORM\OneToMany(targetEntity="Fleet", mappedBy="base")
     */
    private $fleets;
    public function addFleet(Fleet $fleet) { $this->fleets[] = $fleet; return $this; }
    public function removeFleet(Fleet $fleet) { $this->fleets->removeElement($fleet); return $this; }
    public function getFleets() { return $this->fleets; }

    /**
     * @ORM\Column(type="integer")
     */
    private $resources = 1;
    public function setResources($resources) { $this->resources = $resources; return $this; }
    public function getResources() { return $this->resources; }

    /**
     * @ORM\Column(type="integer")
     */
    private $power = 0;
    public function setPower($power) { $this->power = $power; return $this; }
    public function getPower() { return $this->power; }

    /**
     * @ORM\Column(type="integer")
     */
    private $economy = 0;
    public function setEconomy($economy) { $this->economy = $economy; return $this; }
    public function getEconomy() { return $this->economy; }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fleets = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function produceShips()
    {
        $this->power += $this->economy;

        return $this;
    }
}