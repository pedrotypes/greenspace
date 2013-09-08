<?php
namespace My\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Template
 */
class PagesController extends Controller
{
    public function homeAction()
    {
        return [];
    }
}
