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
    // Player colors
    public static $COLORS = [
        '#00ff00', // red
        '#0000ff', // blue
        '#00ff00', // green
        '#ffd000', // yellow
        '#ff8000', // orange
        '#aa00ff', // purple
    ];

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
     * @ORM\Column(type="string")
     */
    private $color;
    public function setColor($color) { $this->color = $color; return $this; }
    public function getColor() { return $this->color; }
    public function getRandomColor()
    {
        return static::$COLORS[rand(0, count(static::$COLORS) - 1)];
    }
    public function selectColor($players)
    {
        $usedColors = [];
        foreach ($players as $p) $usedColors[] = $p->getColor();

        while (!$this->color) {
            $color = $this->getRandomColor();
            if (!in_array($color, $usedColors)) $this->color = $color;
        }
    }

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
            'user'  => $this->user ? $this->user->getId() : null,
            'name'  => $this->user ? $this->user->getName() : null,
            'color'  => $this->color,
        ];
    }

    public function countShips()
    {
        $power = 0;
        foreach ($this->fleets as $fleet) { $power+= $fleet->getPower(); }
        foreach ($this->bases as $base) { $power+= $base->getPower(); }
        return $power;
    }

    public function getProduction()
    {
        $prod = 0;
        foreach ($this->bases as $base) { $prod += $base->getProduction(); }

        return $prod;
    }
}