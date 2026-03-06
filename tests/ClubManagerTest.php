<?php

namespace App\Tests\Service;

use App\Entity\Club;
use App\Entity\User;
use App\Service\ClubManager;
use PHPUnit\Framework\TestCase;

class ClubManagerTest extends TestCase
{

    public function testClubValide()
{
    $club = new Club();
    $club->setNom("Club Robotique");

    $user = new User();
    $club->setResponsable($user);

    $manager = new ClubManager();

    $this->assertTrue($manager->validate($club));
}
    public function testClubSansNom()
{
    $this->expectException(\InvalidArgumentException::class);

    $club = new Club();

    $user = new User();
    $club->setResponsable($user);

    $manager = new ClubManager();

    $manager->validate($club);
}

    public function testClubSansResponsable()
{
    $this->expectException(\InvalidArgumentException::class);

    $club = new Club();
    $club->setNom("Club IA");

    $manager = new ClubManager();

    $manager->validate($club);
}
}