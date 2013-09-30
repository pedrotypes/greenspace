<?php
// src/Acme/StoreBundle/Entity/ProductRepository.php
namespace My\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;


class FleetRepository extends EntityRepository
{
    public function getMoving(Game $game)
    {
        $q = $this
            ->getEntityManager()
            ->createQuery("
                SELECT f FROM MyMainBundle:Fleet f
                JOIN f.player p
                JOIN p.game g
                WHERE
                    g.id = :game
                    AND f.destination IS NOT NULL
            ")
            ->setParameters([
                ':game' => $game,
            ])
        ;

        return $q->getResult();
    }

    public function getOverHostileBases(Game $game)
    {
        $q = $this
            ->getEntityManager()
            ->createQuery("
                SELECT f FROM MyMainBundle:Fleet f
                JOIN f.base b
                JOIN f.player p
                JOIN p.game g
                WHERE
                    g.id = :game
                    AND (b.player IS NULL OR b.player != f.player)
                    AND f.origin IS NULL
                    AND f.destination IS NULL
            ")
            ->setParameters([
                ':game' => $game,
            ])
        ;

        return $q->getResult();
    }
}