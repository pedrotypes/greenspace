<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Map;
use My\MainBundle\Entity\Player;


/**
 * @ORM\Entity
 * @ORM\Table(name="games")
 */
class Game extends BaseEntity
{
    /**
     * @ORM\OneToMany(targetEntity="Player", mappedBy="game")
     */
    private $players;
    public function addPlayer(Player $player) { $this->players[] = $player; return $this; }
    public function removePlayer(Player $player) { $this->players->removeElement($player); return $this; }
    public function getPlayers() { return $this->players; }

    /**
     * @ORM\OneToOne(targetEntity="Map", mappedBy="game")
     */
    private $map;
    public function setMap(Map $map) { $this->map = $map; return $this; }
    public function getMap() { return $this->map; }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
    }
}