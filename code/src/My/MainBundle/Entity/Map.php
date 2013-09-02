<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Map;
use My\MainBundle\Entity\Player;


/**
 * @ORM\Entity
 * @ORM\Table(name="maps")
 */
class Map extends BaseEntity
{
    /**
     * @ORM\OneToOne(targetEntity="Game", inversedBy="map")
     */
    private $game;
    public function setGame(Game $game) { $this->game = $game; return $this; }
    public function getGame() { return $this->game; }

    /**
     * @ORM\OneToMany(targetEntity="Base", mappedBy="map")
     */
    private $bases;
    public function addBase(Base $base) { $this->bases[] = $base; return $this; }
    public function removeBase(Base $base) { $this->bases->removeElement($base); return $this; }
    public function getBases() { return $this->bases; }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->bases = new \Doctrine\Common\Collections\ArrayCollection();
    }
}