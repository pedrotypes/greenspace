<?php
// src/Acme/DemoBundle/Command/GreetCommand.php
namespace My\MainBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use My\MainBundle\Entity\Game;


class GameStartCommand extends ContainerAwareCommand
{
    protected $em; // EntityManager
    protected $input;
    protected $output;


    protected function configure()
    {
        $this
            ->setName('game:start')
            ->setDescription('Start the game')
            ->addArgument('id', InputArgument::REQUIRED, 'Game id')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine')->getManager();
        
        $game = $this->em
            ->getRepository('MyMainBundle:Game')
            ->find($input->getArgument('id'))
        ;
        if (!$game) {
            throw new \Exception('Game not found');
        }

        $game
            ->setHasStarted(true)
            ->setIsRunning(true)
        ;
        $this->em->persist($game);
        $this->em->flush();

        $output->writeln("Game started");
    }
}