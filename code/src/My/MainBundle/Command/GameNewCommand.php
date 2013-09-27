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

    protected $bases = [];

    const DEFAULT_MAP_WIDTH = 500;


    protected function configure()
    {
        $this
            ->setName('game:new')
            ->setDescription('Start a new game')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Game name')
            ->addOption('width', null, InputOption::VALUE_REQUIRED, 'Map width')
            ->addOption('min-distance', null, InputOption::VALUE_REQUIRED, 'Minimum distance between stars')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        $this->input = $input;
        $this->output = $output;

        $game = $this->createGame();
        $map = $this->createMap($game);
        $bases = $this->generateBases($map);

        $this->em->flush();

        $output->writeln("Game #" . $game->getId() . " ready for players.");
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
        $map
            ->setGame($game)
            ->setWidth($this->input->getOption('width') ?: 500)
        ;

        $this->em->persist($map);
        $this->output->writeln('Map created');

        return $map;
    }

    protected function generateBases(Map $map)
    {
        $n = (int) $this->input->getOption('width') ?: static::DEFAULT_MAP_WIDTH;
        $minDistance = $this->input->getOption('min-distance') ?: 50;
        $baseCount = 0;
        $bases = [];

        $starnames = file(__DIR__ . '/../../../../docs/starnames.txt');

        for ($x = 1; $x < $n; $x++) {
            for ($y = 1; $y < $n; $y++) {
                if (!$this->canHazBase($x, $y, $n)) continue;
                if (!$this->canHazPadding($x, $y, $minDistance)) continue;
                if (count($starnames) == 0) continue;

                $nameIndex = rand(0, count($starnames)-1);
                $name = trim($starnames[$nameIndex]);
                unset($starnames[$nameIndex]);
                $starnames = array_values($starnames);

                $base = new Base;
                $base
                    ->setName($name)
                    ->setMap($map)
                    ->setResources(rand(Base::MIN_DEFAULT_RESOURCES, Base::MAX_DEFAULT_RESOURCES))
                    ->setX($x)
                    ->setY($y)
                ;

                $this->em->persist($base);
                $this->indexBase($base);
                $bases[] = $base;
                $baseCount++;
            }
        }

        $this->output->writeln($baseCount . ' bases generated');

        return $bases;
    }

    protected function canHazBase($x, $y, $width)
    {
        $center = $width / 2;
        $distanceToCenter = sqrt(pow($center - $x, 2) + pow($center - $y, 2));

        $odds = 10000;
        $probability = 3;
        
        return rand(0, $odds) < $probability;
    }

    protected function canHazPadding($x, $y, $minDistance)
    {
        for ($i = $x - floor($minDistance); $i <= $x + floor($minDistance); $i++) {
            if (isset($this->bases[$i])) {
                for ($j = $y - floor($minDistance); $j <= $y + floor($minDistance); $j++) {
                    if (isset($this->bases[$i][$j])) return false;
                }
            }
        }

        return true;
    }

    protected function indexBase(Base $base)
    {
        $this->bases[$base->getX()][$base->getY()] = $base;
    }
}