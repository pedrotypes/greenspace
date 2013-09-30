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
            return $this->fail("It's over 9000");

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

        return new Response($fleet->getId());
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
            // cancel movement
            if ($destination == $f->getBase()) {
                $f->clearOrigin();
                $f->clearDestination();
            }
            // start movement
            else {
                $f->setOrigin($f->getBase());
                $f->setDestination($destination);
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
