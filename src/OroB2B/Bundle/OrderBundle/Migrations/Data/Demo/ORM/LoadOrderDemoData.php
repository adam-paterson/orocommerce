<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\OrderBundle\Entity\Order;

class LoadOrderDemoData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User[] $users */
        $users = $manager->getRepository('OroUserBundle:User')->findAll();

        // create orders
        foreach ($users as $user) {
            $order = new Order();
            $order->setOwner($user)
                ->setOrganization($user->getOrganization())
                ->setIdentifier($user->getId());
            $manager->persist($order);
        }

        $manager->flush();
    }
}