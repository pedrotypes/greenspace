<?php
namespace My\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Map;
use My\MainBundle\Entity\User;


/**
 * @ORM\Entity
 * @ORM\Table(name="players")
 */
class Player extends BaseEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="Game", inversedBy="players")
     */
    private $game;
    public function setGame(Game $game) { $this->game = $game; return $this; }
    public function getGame() { return $this->game; }

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="players")
     */
    private $user;
    public function setUser(User $user) { $this->user = $user; return $this; }
    public function getUser() { return $this->user; }

    /**
     * @ORM\OneToMany(targetEntity="Base", mappedBy="player")
     */
    private $bases;
    public function addBase(Base $base) { $this->bases[] = $base; return $this; }
    public function removeBase(Base $base) { $this->bases->removeElement($base); return $this; }
    public function getBases() { return $this->bases; }

    /**
     * @ORM\OneToMany(targetEntity="Fleet", mappedBy="player")
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
        $this->bases = new \Doctrine\Common\Collections\ArrayCollection();
        $this->fleets = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getCard()
    {
        return [
            'id'    => $this->id,
            'user'  => $this->user->getId(),
            'name'  => $this->user->getName(),
        ];
    }
}