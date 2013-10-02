<?php
/**
 * Outputs report cards for Bases
 */
namespace My\MainBundle\Model;

use My\MainBundle\Entity\Base;


class BaseCard
{
    protected $base;

    // Base attributes everyone can see
    protected $common = [
        'id',
        'name',
        'x',
        'y',
        'player',
        'resources',
    ];


    public function __construct(Base $base)
    {
        $this->base = $base;
    }

    public function invisible()
    {
        $data = [];
        foreach ($this->common as $attribute) {
            $getter = 'get' . ucfirst(strtolower($attribute));
            $data[$attribute] = $this->base->{$getter}();
        }

        return $this->makeCard($data);
    }

    public function visible()
    {
        $data = $this->invisible();

        $data['production'] = $this->base->getProduction();
        $data['power'] = $this->base->getPower();
        $data['garrison'] = $this->base->garrison;
        

        return $data;
    }


    protected function makeCard($data)
    {
        $visibleOnlyFields = ['production', 'power', 'garrison'];
        foreach ($visibleOnlyFields as $f) {
            $data[$f] = @$data[$f] ?: null;
        }
        $data['player'] = $this->base->getPlayerCard();

        return $data;
    }
}