<?php
// src/Acme/StoreBundle/Entity/ProductRepository.php
namespace My\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;


class BaseRepository extends EntityRepository
{
    public function findOccupied()
    {
        $q = $this->getEntityManager()->createQuery("
            SELECT b FROM MyMainBundle:Base b
            WHERE b.player IS NOT NULL
        ");

        return $q->getResult();
    }

    public function findByGame(Game $game)
    {
        $q = $this
            ->getEntityManager()
            ->createQuery("
                SELECT
                    b, SUM(f.power) as garrison
                FROM MyMainBundle:Base b
                    
                LEFT JOIN b.fleets AS f
                JOIN b.map m WITH m.game = :game

                GROUP BY b.id
            ")
            ->setParameters([
                ':game' => $game,
            ])
        ;

        $results = $q->getResult();
        $bases = [];

        foreach ($results as $row) {
            $base = $row[0];
            $base->garrison = $row['garrison'];
            $bases[] = $base;
        }

        return $bases;
    }
}