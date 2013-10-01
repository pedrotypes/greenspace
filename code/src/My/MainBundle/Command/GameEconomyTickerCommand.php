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


class GameEconomyTickerCommand extends ContainerAwareCommand
{
    protected $em; // EntityManager
    protected $input;
    protected $output;

    const TURN_SLEEP = 60; // In seconds


    protected function configure()
    {
        $this
            ->setName('game:ticker:economy')
            ->setDescription('Governs economy and ship production')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Time between turn updates, in seconds')
            ->addOption('game', null, InputOption::VALUE_REQUIRED, 'Game Id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->input = $input;
        $this->output = $output;

        $turnSleep = $this->input->getOption('sleep') ?: static::TURN_SLEEP;

        $this->game = $this->getRepository('Game')->find($this->input->getOption('game'));
        if (!$this->game) { throw new \Exception('Invalid game'); }

        $turn = 0;
        while (1) {
            $startTime = microtime(true);
            $turn++;
            
            $this
                ->baseProduction()
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


    protected function getRepository($entity)
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('MyMainBundle:'.ucfirst($entity))
        ;
    }
}