<?php
/**
 * Wrapper class for entity collections
 */
namespace My\MainBundle\Model;

use My\MainBundle\Entity\Base;


abstract class CommonCollection
{
    protected $index = [];

    public function __construct($entities)
    {
        foreach ($entities as $e) $this->index[$e->getId()] = $e;
    }
}