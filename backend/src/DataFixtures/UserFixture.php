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
            ['username' => 'admin1', 'roles' => ['ROLE_ADMIN']],
            ['username' => 'admin2', 'roles' => ['ROLE_ADMIN']],
            ['username' => 'user1', 'roles' => ['ROLE_USER']],
            ['username' => 'user2', 'roles' => ['ROLE_USER']],
        ];

        $defaultPassword = 'Password!234';

        foreach ($users as $userData) {
            $user = new User();
            $user->setUsername($userData['username']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $defaultPassword));

            $manager->persist($user);
        }

        $manager->flush();
    }
}
