<?php
namespace My\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use My\MainBundle\Entity\Game;
use My\MainBundle\Entity\Player;
use My\MainBundle\Entity\Fleet;
use My\MainBundle\Entity\Base;


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

        $state = [
            'bases' => [],
            'fleets' => [],
        ];

        foreach ($rawBases as $b) {
            $data = $this->getStateDataForBase($b, $rawBases, $myPlayer);
            $state['bases'][] = $data;
        }

        // Showing all fleets for all players
        // TODO: Show only detected fleets
        foreach ($game->getPlayers() as $player) {
            foreach ($player->getFleets() as $fleet) {
                $data = $this->getStateDataForFleet($fleet);
                $state['fleets'][] = $data;
            }
        }

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


    protected function getBasesInRange(Base $origin, $bases, Player $player)
    {
        // skip bases not belonging to active player
        if ($origin->getPlayer() != $player) return [];

        $inRange = [];

        foreach ($bases as $b) {
            $range = $origin->getDistanceToBase($b);
            if ($range <= Fleet::DEFAULT_RANGE) {
                $inRange[] = [
                    'id'        => $b->getId(),
                    'name'      => $b->getName(),
                    'distance'  => ceil($range),
                ];
            }
        }

        usort($inRange, function($a, $b) {
            if ($a['distance'] > $b['distance']) return 1;
            elseif ($a['distance'] < $b['distance']) return -1;
            else return 0;
        });

        return $inRange;
    }

    protected function getStateDataForBase(Base $b, $rawBases, Player $myPlayer)
    {
        $power = $myPlayer == $b->getPlayer() ? $b->getPower() : '?';

        $data = [
            'power' => $power,
            'base' => [
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
                'power'     => $power,
            ],
            'fleetCount' => 0,
            'fleetPower' => 0,
            'fleetRange' => Fleet::DEFAULT_RANGE,
            'inRangeBases' => $this->getBasesInRange($b, $rawBases, $myPlayer),
            'fleets' => [],
        ];

        foreach ($b->getFleets() as $f) {
            $data['fleetCount']++;
            $data['power'] += $f->getPower();
            $data['fleetPower'] += $f->getPower();
            $data['fleets'][] = [
                'id'        => $f->getId(),
                'player'    => $f->getPlayer()->getCard(),
                'power'     => $f->getPower(),
                'destination' => $f->getDestination() ? $f->getDestination()->getName() : null,
            ];
        }

        return $data;
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
}
