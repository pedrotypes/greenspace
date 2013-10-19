<?php
/**
 * Wrapper class for fleet collections
 */
namespace My\MainBundle\Model;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Player;


class FleetCollection extends CommonCollection
{
    protected $owned = []; // list of owned fleets, indexed by Id
    protected $visible = []; // list of visible fleets, indexed by Id

    public function viewBy(Player $player, BaseCollection $bases)
    {
        $this->indexOwned($player);
        $this->indexVisible($player, $bases);

        $cards = [];
        foreach ($this->index as $f) {
            $card = new FleetCard($f, $player->getGame());

            if (isset($this->visible[$f->getId()])) $cards[] = $card->visible();
        }

        return $cards;
    }


    protected function indexOwned(Player $player)
    {
        $this->owned = [];
        foreach ($this->index as $f) {
            if ($f->getPlayer() == $player) $this->owned[$f->getId()] = $f;
        }
    }

    protected function indexVisible(Player $player, BaseCollection $bases)
    {
        $this->visible = [];
        if (!$this->owned) $this->indexOwned($player);

        foreach ($this->index as $f) {
            // Owned fleets
            if ($f->getPlayer() == $player) {
                $this->visible[$f->getId()] = $f;
                continue;
            }

            // Fleets within detection of a base
            foreach ($bases->getOwnedBy($player) as $o) {
                if ($f->getDistanceToBase($o) <= Base::DEFAULT_DETECTION_RANGE) {
                    $this->visible[$f->getId()] = $f;
                    break;
                }
            }
        }
    }
}