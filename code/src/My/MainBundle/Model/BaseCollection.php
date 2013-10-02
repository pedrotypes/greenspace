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
            if ($b->getPlayer() == $player) $this->owned[$b->getId()] = $b;
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
                if ($o->getDistanceToBase($b) <= Base::DEFAULT_DETECTION_RANGE) {
                    $this->visible[$b->getId()] = $b;
                    break;
                }
            }
        }
    }
}