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
     * @ORM\Column(type="boolean")
     */
    private $hasStarted = false;
    public function setHasStarted($hasStarted) { $this->hasStarted = $hasStarted; return $this; }
    public function getHasStarted() { return $this->hasStarted; }
    public function hasStarted() { return $this->getHasStarted(); }

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isRunning = false;
    public function setIsRunning($isRunning) { $this->isRunning = $isRunning; return $this; }
    public function getIsRunning() { return $this->isRunning; }
    public function isRunning() { return $this->getIsRunning(); }

    /**
     * Time to wait between ship movements, in seconds
     * @ORM\Column(type="integer")
     */
    private $movementTimeout = 10;
    public function setMovementTimeout($movementTimeout) { $this->movementTimeout = $movementTimeout; return $this; }
    public function getMovementTimeout() { return $this->movementTimeout; }

    /**
     * Time to wait between economy and production turns
     * @ORM\Column(type="integer")
     */
    private $economyTimeout = 3600;
    public function setEconomyTimeout($economyTimeout) { $this->economyTimeout = $economyTimeout; return $this; }
    public function getEconomyTimeout() { return $this->economyTimeout; }

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastEconomyUpdate;
    public function setLastEconomyUpdate($lastEconomyUpdate) { $this->lastEconomyUpdate = $lastEconomyUpdate; return $this; }
    public function getLastEconomyUpdate() { return $this->lastEconomyUpdate; }

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastMovementUpdate;
    public function setLastMovementUpdate($lastMovementUpdate) { $this->lastMovementUpdate = $lastMovementUpdate; return $this; }
    public function getLastMovementUpdate() { return $this->lastMovementUpdate; }


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

    public function needsMovementUpdate()
    {
        if (!$this->getLastMovementUpdate()) return true;

        $now = time();
        $updateRequiredTime = $this->getLastMovementUpdate()->getTimestamp()
            + $this->getMovementTimeout();


        return $now >= $updateRequiredTime;
    }

    public function needsEconomyUpdate()
    {
        if (!$this->getLastEconomyUpdate()) return true;

        $now = time();
        $updateRequiredTime = $this->getLastEconomyUpdate()->getTimestamp()
            + $this->getEconomyTimeout();

        
        return $now >= $updateRequiredTime;
    }
}