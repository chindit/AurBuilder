<?php

namespace App\DataFixtures;

use App\Entity\Package;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PackageFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $package = (new Package())
            ->setPackageId(23)
            ->setName('chindit')
            ->setVersion('0.0.1')
            ->setDescription('Nic description');
        $manager->persist($package);

        $manager->flush();
    }
}
