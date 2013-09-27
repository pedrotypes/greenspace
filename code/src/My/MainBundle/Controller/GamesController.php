<?php
namespace My\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use My\MainBundle\Entity\Game;
use My\MainBundle\Entity\Player;


/**
 * @Template
 */
class GamesController extends Controller
{
    public function indexAction()
    {
        $games = $this->getDoctrine()->getManager()
            ->getRepository('MyMainBundle:Game')
            ->findAll()
        ;

        return ['games' => $games];
    }

    public function showAction(Game $game)
    {
        $action = $game->hasStarted() ?
            'MyMainBundle:Games:map' : 'MyMainBundle:Games:lobby'
        ;

        return $this->forward($action, ['game' => $game]);
    }

    public function lobbyAction(Game $game)
    {
        $players = $game->getPlayers();

        return $this->render('MyMainBundle:Games:lobby.html.twig', [
            'game' => $game,
            'players' => $players,
        ]);
    }

    public function mapAction(Game $game)
    {
        $map = $game->getMap();
        $bases = $map->getBases();
        
        return $this->render('MyMainBundle:Games:map.html.twig', [
            'game'  => $game,
            'map'   => $map,
            'bases' => $bases,
            'player' => $game->getPlayerForUser($this->getUser()),
        ]);
    }

    public function stateAction(Game $game)
    {
        $myPlayer = $game->getPlayerForUser($this->getUser());
        $rawBases = $game->getMap()->getBases();
        $bases = [];

        foreach ($rawBases as $b) {
            $data = [
                'id'        => $b->getId(),
                'name'      => $b->getName(),
                'x'         => $b->getX(),
                'y'         => $b->getY(),
                'owned'     => $myPlayer == $b->getPlayer(),
                'neutral'   => !$b->getPlayer(),
                'enemy'     => $b->getPlayer() && $b->getPlayer() != $myPlayer,
                'player'    => $b->getPlayerCard(),
                'resources' => $b->getResources(),
                'economy'   => $myPlayer == $b->getPlayer() ? $b->getEconomy() : '?',
                'power'     => $myPlayer == $b->getPlayer() ? $b->getPower() : '?',
            ];

            $bases[] = $data;
        }
        
        die(json_encode($bases));
    }

    public function joinAction(Game $game)
    {
        $me = $this->getUser();

        if (!$game->hasStarted() && !$game->hasUser($me)) {
            $player = new Player;
            $player
                ->setUser($me)
                ->setGame($game)
            ;

            $em = $this->getDoctrine()->getManager();
            $em->persist($player);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('games_show', [
            'game' => $game->getId(),
        ]));
    }

    public function leaveAction(Game $game)
    {
        $me = $this->getUser();

        if (!$game->hasStarted() && $game->hasUser($me)) {
            $player = $game->getPlayerForUser($me);
            $game->getPlayers()->removeElement($player);

            $em = $this->getDoctrine()->getManager();
            $em->remove($player);
            $em->persist($game);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('games_show', [
            'game' => $game->getId(),
        ]));
    }
}
