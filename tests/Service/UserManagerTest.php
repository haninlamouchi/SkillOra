<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserManager;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase
{
    private UserManager $manager;

    protected function setUp(): void
    {
        $this->manager = new UserManager();
    }

    // ✅ Test 1 : User valide
    public function testValidUser(): void
    {
        $user = new User();
        $user->setNom('Dabbek');
        $user->setPrenom('Malek');
        $user->setEmail('malek@gmail.com');
        $user->setTelephone('+21622412029');
        $user->setDateNaissance(new \DateTime('2000-01-01'));

        $this->assertTrue($this->manager->validate($user));
    }

    // ❌ Test 2 : Nom vide
    public function testUserWithoutNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom est obligatoire');

        $user = new User();
        $user->setNom('');
        $user->setPrenom('Malek');
        $user->setEmail('malek@gmail.com');

        $this->manager->validate($user);
    }

    // ❌ Test 3 : Prénom vide
    public function testUserWithoutPrenom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom est obligatoire');

        $user = new User();
        $user->setNom('Dabbek');
        $user->setPrenom('');
        $user->setEmail('malek@gmail.com');

        $this->manager->validate($user);
    }

    // ❌ Test 4 : Email invalide
    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email invalide');

        $user = new User();
        $user->setNom('Dabbek');
        $user->setPrenom('Malek');
        $user->setEmail('email_invalide');

        $this->manager->validate($user);
    }

    // ❌ Test 5 : Téléphone trop court
    public function testUserWithInvalidTelephone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Numéro de téléphone invalide');

        $user = new User();
        $user->setNom('Dabbek');
        $user->setPrenom('Malek');
        $user->setEmail('malek@gmail.com');
        $user->setTelephone('123');

        $this->manager->validate($user);
    }

    // ❌ Test 6 : Date de naissance dans le futur
    public function testUserWithFutureDateNaissance(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de naissance doit être dans le passé');

        $user = new User();
        $user->setNom('Dabbek');
        $user->setPrenom('Malek');
        $user->setEmail('malek@gmail.com');
        $user->setDateNaissance(new \DateTime('2099-01-01'));

        $this->manager->validate($user);
    }
}