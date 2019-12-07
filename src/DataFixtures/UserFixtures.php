<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('admin');
        $password = $this->encoder->encodePassword($user, 'password');
        $user->setPassword($password);
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setEmail("test@email.com");
        $user->setFirstname("Yanis");
        $user->setLastname("Vuillecard");
        $user->setRank(2);
        $user->setEnabled(1);
        $manager->persist($user);
        $manager->flush();

        $user = new User();
        $user->setUsername('user');
        $password = $this->encoder->encodePassword($user, 'password');
        $user->setPassword($password);
        $user->setRoles(["ROLE_USER"]);
        $user->setEmail("test2@email.com");
        $user->setFirstname("Jean");
        $user->setLastname("Neymar");
        $user->setRank(1);
        $user->setEnabled(1);
        $manager->persist($user);
        $manager->flush();
    }
}
