<?php
// src/Acme/DemoBundle/Command/GreetCommand.php
namespace My\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use My\MainBundle\Entity\Base;
use My\MainBundle\Entity\Game;
use My\MainBundle\Entity\Map;


class GameTickerCommand extends ContainerAwareCommand
{
    protected $em; // EntityManager
    protected $input;
    protected $output;

    const TURN_SLEEP = 1; // In seconds


    protected function configure()
    {
        $this
            ->setName('game:ticker')
            ->setDescription('Game ticker engine')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->input = $input;
        $this->output = $output;

        $gamesRepo = $this->getRepository('Game');
        $fleetsRepo = $this->getRepository('Fleet');

        $turn = 0;
        while (1) {
            $startTime = microtime(true);
            $turn++;
            $now = new \DateTime();

            $this->em->clear();
            $games = $gamesRepo->findByIsRunning(true);

            foreach ($games as $game) {
                if ($game->needsMovementUpdate()) {
                    $this->output->writeln('[M] ['.$now->format('Y-m-d H:i:s').'] Game '.$game->getId());
                    // $this
                    //     ->fleetMovement($game)
                    //     ->baseConquest($game)
                    // ;

                    $game->setLastMovementUpdate($now);
                    $this->em->persist($game);
                }

                if ($game->needsEconomyUpdate()) {
                    $this->output->writeln('[E] ['.$now->format('Y-m-d H:i:s').'] Game '.$game->getId());
                    $this
                        ->baseProduction($game)
                    ;

                    $game->setLastEconomyUpdate($now);
                    $this->em->persist($game);
                }
            }

            $this->em->flush();
            sleep(static::TURN_SLEEP);
        }
    }


    protected function getRepository($entity)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('MyMainBundle:'.ucfirst($entity))
        ;
    }

    // All moving fleets creep towards their destinations
    protected function fleetMovement(Game $game)
    {
        $fleets = $this->getRepository('Fleet')->getMoving($game);

        foreach ($fleets as $fleet) {
            $fleet
                ->clearBase() // Leave origin
                ->move(Fleet::DEFAULT_SPEED)
            ;

            if ($fleet->hasArrived()) {
                $fleet
                    ->setBase($fleet->getDestination())
                    ->clearOrigin()
                    ->clearDestination()
                    ->setDistance(null)
                ;
            }

            $this->em->persist($fleet);
        }

        $this->em->flush();

        return $this;
    }

    // Fleets conquer the bases they're occupying
    protected function baseConquest(Game $game)
    {
        $fleets = $this->getRepository('Fleet')->getOverHostileBases($game);
        $bases = [];

        foreach ($fleets as $fleet) {
            $base = $fleet->getBase();
            $bases[$base->getId()] = $base;
        }

        foreach ($bases as $base) {
            $conquered = true;
            $player = null;

            foreach ($base->getFleets() as $fleet) {
                $player = $player ?: $fleet->getPlayer();

                // Is there a fleet here that doesn't belong to the base owner?
                if ($base->getPower() && $base->getPlayer() && $base->getPlayer() != $fleet->getPlayer()) {
                    // Then add base power as a defending fleet
                    $fleet = (new Fleet)
                        ->setPlayer($base->getPlayer())
                        ->setBase($base)
                        ->setPower($base->getPower())
                    ;

                    $base->setPower(0);
                    $base->addFleet($fleet);

                    $this->em->persist($fleet);
                    $this->em->persist($base);
                    $this->em->flush();
                }


                // If another player has a fleet, base cannot be conquered
                if ($player != $fleet->getPlayer())
                    $conquered = false;
            }

            // No opposing fleets? Conquer the base
            if ($conquered) {
                $base->conquerBy($player);
                $this->em->persist($base);
            }

            // Are there opposing fleets? It's go time!
            else {
                $this->fleetCombat($base);
            }
        }

        $this->em->flush();

        return $this;
    }

    // This should maybe go to a Battle class
    // And be tested *ahem*
    protected function fleetCombat(Base $base)
    {
        $fleets = $base->getFleets();
        $totalPower = 0;
        foreach ($fleets as $f) $totalPower+= $f->getPower();

        // Pick sides
        $sides = [];
        foreach ($fleets as $f) {
            $key = $f->getPlayer()->getId();

            @$sides[$key]['power'] += $f->getPower();
            @$sides[$key]['fleets'][] = $f;
        }

        // Assign damage
        foreach ($sides as $player => $myside) {
            $opponents = [];
            foreach ($sides as $opposingPlayer => $opposingSide) {
                if ($opposingPlayer == $player) continue;

                // What percentage of the opposing forces is this side?
                $opponentSize = $opposingSide['power'] / ($totalPower - $myside['power']);

                // Hit it with that percentage of our power
                @$sides[$opposingPlayer]['damage'] = $myside['power'] * $opponentSize;
            }
        }

        // Deal damage
        foreach ($sides as $pid => $side) {
            $damage = $side['damage'];

            // Deal damage to fleets
            foreach ($side['fleets'] as $f) {
                // No point in doing this after all damage was assigned
                if ($damage <= 0) continue;

                // Deal damage to fleet
                $f->removePower($damage);
                $damage -= $f->getPower();

                // Remove killed fleets
                if ($f->getPower() <= 0) {
                    echo "Fleet #".$f->getId()." goes BOOM\n";
                    $this->em->remove($f);
                }
            }
        }
    }

    // All occupied bases produce ships according to their economy rating
    protected function baseProduction(Game $game)
    {
        $bases = $this->em
            ->getRepository('MyMainBundle:Base')
            ->findOccupied($game)
        ;
        foreach ($bases as $base) {
            $base->produceShips();
            $this->em->persist($base);
        }

        $this->em->flush();

        return $this;
    }
}