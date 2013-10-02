<?php
namespace My\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use My\MainBundle\Entity\Game;
use My\MainBundle\Entity\Player;
use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Base;

use My\MainBundle\Model\BaseCollection;
use My\MainBundle\Model\FleetCollection;


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
        $bases = new BaseCollection($this->getDoctrine()
            ->getRepository('MyMainBundle:Base')
            ->findByGame($game)
        );
        $fleets = new FleetCollection($this->getDoctrine()
            ->getRepository('MyMainBundle:Fleet')
            ->findByGame($game)
        );

        $state = [
            'status' => [
                'bases'     => $myPlayer->getBases()->count(),
                'production'=> $myPlayer->getProduction(),
                'ships'     => $myPlayer->countShips(),
                'fleets'    => $myPlayer->getFleets()->count(),
            ],
            'bases' => $bases->viewBy($myPlayer),
            'fleets' => $fleets->viewBy($myPlayer, $bases),
        ];

        return new Response(json_encode($state));
    }

    public function joinAction(Game $game)
    {
        $me = $this->getUser();

        if (!$game->hasStarted() && !$game->hasUser($me)) {

            $player = new Player;
            $player
                ->setUser($me)
                ->setGame($game)
                ->selectColor($game->getPlayers())
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


    protected function getStateDataForFleet(Fleet $fleet)
    {
        return [
            'id'            => $fleet->getId(),
            'player'        => $fleet->getPlayer()->getCard(),
            'power'         => $fleet->getPower(),
            'base'          => $fleet->getBaseId(),
            'origin'        => $fleet->getOriginId(),
            'destination'   => $fleet->getDestinationData(),
            'isMoving'      => $fleet->isMoving(),
            'coords'        => $fleet->getCoords(),
            'distance'      => $fleet->getDistance(),
        ];
    }

    protected function baseCards($bases)
    {
        $cards = [];
        foreach ($bases as $b) {
            $cards[] = [
                'id'        => $b->getId(),
                'player'    => $b->getPlayer() ? $b->getPlayer()->getId() : null,
                'name'      => $b->getName(),
                'distance'  => ceil($b->distance),
            ];
        }

        return $cards;
    }
}
