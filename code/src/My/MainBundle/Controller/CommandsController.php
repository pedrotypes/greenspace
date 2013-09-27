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
        if (!$player) $this->fail();
        if ($player->getUser() != $this->getUser()) $this->fail();
        if ($power > $base->getPower()) $this->fail();

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

    protected function fail($message = '') {
        return new Response($message, 400);
    }
}