<?php
/**
 * Handles commands issued by players
 */
namespace My\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use My\MainBundle\Entity\Game;
use My\MainBundle\Form\Type\GameAdminType;


/**
 * @Template
 */
class AdminController extends Controller
{
    public function gamesAction()
    {
        $games = $this->getDoctrine()
            ->getRepository('MyMainBundle:Game')
            ->findAll()
        ;

        $entities = [];
        foreach ($games as $g) {
            $form = $this->createForm(new GameAdminType, $g, [
                'action' => $this->generateUrl('admin_game_edit', ['game' => $g->getId()]),
                'method' => 'POST',
            ]);

            $entities[] = [
                'game' => $g,
                'form' => $form->createView(),
            ];
        }

        return ['entities' => $entities];
    }

    public function gameEditAction(Game $game)
    {
        $form = $this->createForm(new GameAdminType, $game);

        $form->handleRequest($this->getRequest());
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($game);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('admin_games')."#g".$game->getId());
    }
}
