<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Map;
use My\MainBundle\Entity\Player;


/**
 * @ORM\Entity
 * @ORM\Table(name="bases")
 */
class Base extends BaseEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Map", inversedBy="bases")
     */
    private $map;
    public function setMap(Map $map) { $this->map = $map; return $this; }
    public function getMap() { return $this->map; }

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
     * Constructor
     */
    public function __construct()
    {
        $this->fleets = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
}