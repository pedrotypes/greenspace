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


class GameNewCommand extends ContainerAwareCommand
{
    protected $em; // EntityManager
    protected $input;
    protected $output;

    const DEFAULT_BASE_COUNT = 10;


    protected function configure()
    {
        $this
            ->setName('game:new')
            ->setDescription('Start a new game')
            ->addOption('name', 'null', InputOption::VALUE_REQUIRED, 'Game name')
            ->addOption('bases', null, InputOption::VALUE_REQUIRED, 'Number of bases on the map')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->input = $input;
        $this->output = $output;

        $game = $this->createGame();
        $map = $this->createMap($game);
        $bases = $this->populateBases($map);

        $this->em->flush();

        $output->writeln("Done");
    }


    protected function createGame()
    {
        $name = $this->input->getOption('name') ?: 'Game #'.uniqid();
        $game = new Game;
        $game->setName($name);

        $this->em->persist($game);
        $this->output->writeln('Starting game "'.$name.'" ...');

        return $game;
    }

    protected function createMap(Game $game)
    {
        $map = new Map;
        $map->setGame($game);

        $this->em->persist($map);
        $this->output->writeln('Map created');

        return $map;
    }

    protected function populateBases(Map $map)
    {
        $n = (int) $this->input->getOption('bases') ?: static::DEFAULT_BASE_COUNT;
        $bases = [];

        for ($i = 0; $i < $n; $i++) {
            $base = new Base;
            $base
                ->setMap($map)
                ->setEconomy(rand(Base::MIN_DEFAULT_ECONOMY, Base::MAX_DEFAULT_ECONOMY))
            ;

            $this->em->persist($base);
            $this->output->writeln('Base created');
        }

        return $bases;
    }
}