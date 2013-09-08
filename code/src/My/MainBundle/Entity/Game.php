<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Map;
use My\MainBundle\Entity\Player;
use My\MainBundle\Entity\User;


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
     * @ORM\Column(type="string")
     */
    private $name;
    public function setName($name) { $this->name = $name; return $this; }
    public function getName() { return $this->name; }

    /**
     * @ORM\Column(type="string")
     */
    private $hasStarted = false;
    public function setHasStarted($hasStarted) { $this->hasStarted = $hasStarted; return $this; }
    public function getHasStarted() { return $this->hasStarted; }
    public function hasStarted() { return $this->getHasStarted(); }

    /**
     * @ORM\Column(type="string")
     */
    private $isRunning = false;
    public function setIsRunning($isRunning) { $this->isRunning = $isRunning; return $this; }
    public function getIsRunning() { return $this->isRunning; }
    public function isRunning() { return $this->getIsRunning(); }


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->players = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function getPlayerForUser(User $user)
    {
        foreach ($this->getPlayers() as $player)
        {
            if ($player->getUser() == $user) return $player;
        }
    }

    public function hasUser(User $user) 
    {
        return !!$this->getPlayerForUser($user);
    }
}