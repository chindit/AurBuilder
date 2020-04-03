<?php

namespace App\DataFixtures;

use App\Entity\PackageRequest;
use Carbon\Carbon;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PackageRequestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $packageRequest = (new PackageRequest())
            ->setName('aur-builder')
            ->setCreatedAt(Carbon::now());
        $manager->persist($packageRequest);

        $manager->flush();
    }
}
