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

    const TURN_SLEEP = 60; // In seconds


    protected function configure()
    {
        $this
            ->setName('game:ticker')
            ->setDescription('Game ticker engine')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Time between turn updates, in seconds')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->input = $input;
        $this->output = $output;

        $turnSleep = $this->input->getOption('sleep') ?: static::TURN_SLEEP;

        $turn = 0;
        while (1) {
            $startTime = microtime(true);
            $turn++;
            
            $this
                ->baseProduction()
                ->fleetMovement()
                ->fleetCombat()
                ->baseConquest()
            ;

            $output->writeln("Turn #" . $turn . " (".(microtime(true) - $startTime)."s)");
            sleep($turnSleep);
        }
    }


    // All occupied bases produce ships according to their economy rating
    protected function baseProduction()
    {
        $bases = $this->em
            ->getRepository('MyMainBundle:Base')
            ->findOccupied()
        ;
        foreach ($bases as $base) {
            $base->produceShips();
            $this->em->persist($base);
        }

        $this->em->flush();

        return $this;
    }

    // All moving fleets creep towards their destinations
    protected function fleetMovement()
    {


        return $this;
    }

    // Non-aligned fleets occupying the same base have to fight
    protected function fleetCombat()
    {
        return $this;
    }

    // Fleets conquer the bases they're occupying
    protected function baseConquest()
    {

        return $this;
    }
}