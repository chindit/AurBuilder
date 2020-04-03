<?php

namespace App\DataFixtures;

use App\Entity\Release;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ReleaseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $release = (new Release())
            ->setName('chindit')
            ->setLastVersion('0.0.1')
            ->setNewVersion('0.0.2');
        $manager->persist($release);

        $manager->flush();
    }
}
