<?php
/**
 * Outputs report cards for Fleets
 */
namespace My\MainBundle\Model;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Game;
use My\MainBundle\Entity\Fleet;


class FleetCard
{
    protected $fleet;
    protected $game;

    // Fleet attributes everyone can see
    protected $common = [
        'id',
        'power',
    ];


    public function __construct(Fleet $fleet, Game $game)
    {
        $this->fleet = $fleet;
        $this->game = $game;
    }

    public function visible()
    {
        $data = [];
        foreach ($this->common as $attribute) {
            $getter = 'get' . ucfirst(strtolower($attribute));
            $data[$attribute] = $this->fleet->{$getter}();
        }

        return $this->makeCard($data);
    }


    protected function makeCard($data)
    {
        $data['player'] = $this->fleet->getPlayerCard();
        $data['coords'] = array(
            'x' => $this->fleet->getX(),
            'y' => $this->fleet->getY(),
        );

        $data['base']         = $this->fleet->getBaseId();
        $data['origin']       = $this->fleet->getOriginId();
        $data['destination']  = $this->fleet->getDestinationId();
        $data['timeleft']     = $this->fleet->getTimeLeftToDestination($this->game);
        $data['isMoving']     = $this->fleet->isMoving();
        $data['coords']       = $this->fleet->getCoords();
        $data['distance']     = $this->fleet->getDistance();

        return $data;
    }
}