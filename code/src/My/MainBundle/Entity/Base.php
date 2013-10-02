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
    const DEFAULT_DETECTION_RANGE = 100;

    // Used when listing bases relative to a point in space
    public $distance = null;
    // Array of bases in range of this one
    public $inRange = [];


    /**
     * @ORM\Column(type="string")
     */
    private $name;
    public function setName($name) { $this->name = $name; return $this; }
    public function getName() { return $this->name; }

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
     * Stationed fleets
     * @ORM\OneToMany(targetEntity="Fleet", mappedBy="base")
     */
    private $fleets;
    public function addFleet(Fleet $fleet) { $this->fleets[] = $fleet; return $this; }
    public function removeFleet(Fleet $fleet) { $this->fleets->removeElement($fleet); return $this; }
    public function getFleets() { return $this->fleets; }

    /**
     * Fleets inbound for this base
     * @ORM\OneToMany(targetEntity="Fleet", mappedBy="destination")
     */
    private $inbound;
    public function addInbound(Fleet $fleet) { $this->inbound[] = $fleet; return $this; }
    public function removeInbound(Fleet $fleet) { $this->inbound->removeElement($fleet); return $this; }
    public function getInbound() { return $this->inbound; }

    /**
     * Fleets leaving this base
     * @ORM\OneToMany(targetEntity="Fleet", mappedBy="origin")
     */
    private $outbound;
    public function addOutbound(Fleet $fleet) { $this->outbound[] = $fleet; return $this; }
    public function removeOutbound(Fleet $fleet) { $this->outbound->removeElement($fleet); return $this; }
    public function getOutbound() { return $this->outbound; }

    /**
     * @ORM\Column(type="string")
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
    public $garrison = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $economy = 0;
    public function setEconomy($economy) { $this->economy = $economy; return $this; }
    public function getEconomy() { return $this->economy; }

    /**
     * @ORM\Column(type="integer")
     */
    private $production = 0;
    public function setProduction($production) { $this->production = $production; return $this; }
    public function getProduction() { return $this->production; }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fleets = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function conquerBy(Player $player)
    {
        $this->player = $player;
        if ($this->production == 0) $this->production = $this->resources;
    }
    
    public function produceShips()
    {
        $this->power += $this->production;

        return $this;
    }

    public function getPlayerCard()
    {
        return $this->player ?
            $this->player->getCard()
            : (new Player)->getCard()
        ;
    }

    public function addPower($power) {
        $this->power += (int) $power;
        if ($this->power < 0) $this->power = 0;
    }

    public function removePower($power)
    {
        $this->power = $this->power - (int) $power;
        if ($this->power < 0) $this->power = 0;
    }

    public function getDistanceToBase(Base $target)
    {
        return $this->getDistance($target->getX(), $target->getY());
    }

    public function getDistance($x, $y)
    {
        return sqrt(pow($x - $this->getX(), 2) + pow($y - $this->getY(), 2));
    }
}