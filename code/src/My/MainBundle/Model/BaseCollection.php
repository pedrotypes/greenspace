<?php
/**
 * Wrapper class for base collections
 */
namespace My\MainBundle\Model;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Player;


class BaseCollection extends CommonCollection
{
    protected $owned = []; // list of owned bases, indexed by base Id
    protected $visible = []; // list of visible bases, indexed by base Id

    public function getOwnedBy(Player $player) {
        $this->indexOwned($player);

        return $this->owned;
    }

    public function viewBy(Player $player)
    {
        $this->indexOwned($player);
        $this->indexVisible($player);

        $cards = [];
        foreach ($this->index as $b) {
            $card = new BaseCard($b);

            if (isset($this->visible[$b->getId()])) $cards[] = $card->visible();
            else $cards[] = $card->invisible();
        }

        return $cards;
    }


    protected function indexOwned(Player $player)
    {
        $this->owned = [];
        foreach ($this->index as $b) {
            if ($b->getPlayer() == $player) {
                $b->isOwned = true;
                $b->inFleetRange = $this->getBasesInFleetRange($b);
                $this->owned[$b->getId()] = $b;
            }
        }
    }

    protected function indexVisible(Player $player)
    {
        $this->visible = [];
        if (!$this->owned) $this->indexOwned($player);

        foreach ($this->index as $b) {
            // Owned bases
            if ($b->getPlayer() == $player) {
                $this->visible[$b->getId()] = $b;
                continue;
            }

            // Bases within detection range
            foreach ($this->owned as $o) {
                if ($o->isInDetectionRangeOf($b)) {
                    $this->visible[$b->getId()] = $b;
                    break;
                }
            }
        }
    }

    protected function getBasesInFleetRange(Base $base)
    {
        $inRange = [];
        foreach ($this->index as $b) {
            if ($b->isInFleetRangeOf($base)) {
                $inRange[] = $b;
            }
        }

        return $inRange;
    }
}