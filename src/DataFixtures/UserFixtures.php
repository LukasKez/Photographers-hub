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
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setPassword($this->encoder->encodePassword(
            $user,
            '123456'
        ));
        $user->setEmail('admin@hub.com');
        $user->setIsPhotographer(false);
        $user->setCity('Kaunas');
        $user->setState('Kaunas county');
        $user->setCountry('Lithuania'); 

        $manager->persist($user);
        $manager->flush();
    }
}
