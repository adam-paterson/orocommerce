<?php

namespace OroB2B\Bundle\WebsiteBundle\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class WebsiteManager
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return Website
     */
    public function getCurrentWebsite()
    {
        return $this->getEntityManager()->getRepository('OroB2BWebsiteBundle:Website')->findOneBy(
            [],
            ['id' => Criteria::ASC]
        );
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass('OroB2BWebsiteBundle:Website');
    }
}
