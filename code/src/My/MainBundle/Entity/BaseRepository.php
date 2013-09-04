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
}