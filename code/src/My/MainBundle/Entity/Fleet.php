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
    /**
     * @ORM\ManyToOne(targetEntity="Base", inversedBy="fleets")
     */
    private $base;
    public function setBase(Base $base) { $this->base = $base; return $this; }
    public function getBase() { return $this->base; }

    /**
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="fleets")
     */
    private $player;
    public function setPlayer(Player $player) { $this->player = $player; return $this; }
    public function getPlayer() { return $this->player; }
}