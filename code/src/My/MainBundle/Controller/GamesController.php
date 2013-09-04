<?php
namespace My\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use My\MainBundle\Entity\Game;


/**
 * @Template
 */
class GamesController extends Controller
{
    public function showAction(Game $game)
    {
        return ['game' => $game];
    }
}
