<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            ['username' => 'admin1@gmail.com', 'roles' => ['ROLE_ADMIN'], 'name' => 'admin 1'],
            ['username' => 'admin2@gmail.com', 'roles' => ['ROLE_ADMIN'], 'name' => 'admin 2'],
            ['username' => 'user1@gmail.com', 'roles' => ['ROLE_USER'], 'name' => 'user 1'],
            ['username' => 'user2@gmail.com', 'roles' => ['ROLE_USER'], 'name' => 'user 2'],
        ];

        $defaultPassword = 'Password!234';

        foreach ($users as $userData) {
            $user = new User();
            $user->setUsername($userData['username']);
            $user->setRoles($userData['roles']);
            $user->setName($userData['name']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $defaultPassword));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
