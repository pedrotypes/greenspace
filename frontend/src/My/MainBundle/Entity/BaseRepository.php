<?php
// src/Acme/StoreBundle/Entity/ProductRepository.php
namespace My\MainBundle\Entity;

use Doctrine\ORM\EntityRepository;

use My\MainBundle\Entity\Game;


class BaseRepository extends EntityRepository
{
    public function findOccupied(Game $game)
    {
        $q = $this->getEntityManager()
            ->createQuery("
                SELECT b FROM MyMainBundle:Base b
                JOIN b.map m WITH m.game = :game
                WHERE b.player IS NOT NULL
            ")
            ->setParameter(':game', $game)
        ;

        return $q->getResult();
    }

    public function findByGame(Game $game)
    {
        $q = $this
            ->getEntityManager()
            ->createQuery("
                SELECT
                    b, SUM(f.power) as fleetPower
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
            $base->fleetPower = $row['fleetPower'] ?: 0;
            $bases[] = $base;
        }

        return $bases;
    }
}