<?php
/**
 * Handles commands issued by players
 */
namespace My\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use My\MainBundle\Entity\Game;
use My\MainBundle\Entity\Player;
use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Fleet;

use My\MainBundle\Model\FleetCard;


class CommandsController extends Controller
{
    public function createFleetAction(Base $base)
    {
        $power = (int) $this->getRequest()->request->get('power');
        if (!$power) $this->fail();

        $player = $base->getPlayer();
        if (!$player)
            return $this->fail("Is there anybody out there?");
        if ($player->getUser() != $this->getUser()) 
            return $this->fail("You are not who you appear to be");
        if ($power > $base->getPower()) 
            $power = $base->getPower(); // Just a soft cap here will do
        if ($power <= 0)
            return $this->fail("I see what you did there");

        $fleet = new Fleet;
        $fleet
            ->setBase($base)
            ->setPlayer($player)
            ->setPower($power)
        ;

        $base->removePower($power);

        $em = $this->getDoctrine()->getManager();
        $em->persist($fleet);
        $em->persist($base);
        $em->flush();

        $card = new FleetCard($fleet, $base->getMap()->getGame());

        return new Response(json_encode($card->visible()));
    }

    public function stationFleetsAction(Base $base)
    {
        $player = $base->getPlayer();
        $fleets = $base->getFleets();
        $selectedFleets = $this->getRequest()->request->get('fleet');

        if (!$player)
            return $this->fail("Is there anybody out there?");
        if ($player->getUser() != $this->getUser()) 
            return $this->fail("You are not who you appear to be");
        if (!$this->validateSelectedFleets($selectedFleets, $fleets))
            return $this->fail("Who are you talking to?");

        $em = $this->getDoctrine()->getManager();
        foreach ($fleets as $f) {
            if ($f->getPlayer() != $player)
                return $this->fail("Leave the fleets alone");
            
            if (in_array($f->getId(), $selectedFleets)) {
                $base->addPower($f->getPower());
                $em->remove($f);
            }
        }

        $em->flush();

        return new Response(json_encode(['Fleets merged']));
    }

    public function moveFleetsAction(Game $game)
    {
        $player = $game->getPlayerForUser($this->getUser());
        $selectedFleets = $this->getRequest()->request->get('fleet');
        $destinationId = $this->getRequest()->request->get('destination');
        $destination = $this->getDoctrine()
            ->getRepository('MyMainBundle:Base')
            ->find($destinationId)
        ;

        if (!$player)
            return $this->fail("Is there anybody out there?");
        if (!$destination)
            return $this->fail("Where do you think you're going?");

        $allFleets = $player->getFleets();
        if (!$this->validateSelectedFleets($selectedFleets, $allFleets))
            return $this->fail("Who are you talking to?");

        $fleets = $this->getSelectedFleets($selectedFleets, $allFleets);

        $em = $this->getDoctrine()->getManager();
        foreach ($fleets as $f) {
            $distance = $f->getBase()->getDistanceToBase($destination);
            if ($distance > Fleet::DEFAULT_RANGE)
                return $this->fail("The hyperdrive motivator seems to be damaged");

            // cancel movement
            if ($destination == $f->getBase()) {
                $f->clearOrigin();
                $f->clearDestination();
                $f->setDistance(null);
            }
            // start movement
            else {
                $f->setOrigin($f->getBase());
                $f->setDestination($destination);
                $f->setDistance($f->getBase()->getDistanceToBase($destination));
            }
            
            $em->persist($f);
        }
        $em->flush();

        return new Response(json_encode(['Fleets moving']));
    }


    protected function fail($message = '') {
        return new Response($message, 400);
    }

    protected function validateSelectedFleets($selectedFleets, $fleets)
    {
        $found = false;
        foreach ($selectedFleets as $sf) {
            $found = false;
            foreach ($fleets as $f) {
                if ($f->getId() == $sf) {
                    $found = true;
                    break;
                }
            }
        }

        return $found;
    }

    protected function getSelectedFleets($selectedFleets, $fleets)
    {
        $found = [];
        foreach ($selectedFleets as $sf) {
            foreach ($fleets as $f) {
                if ($f->getId() == $sf) {
                    $found[] = $f;
                }
            }
        }

        return $found;
    }
}
