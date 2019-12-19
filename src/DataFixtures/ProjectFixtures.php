<?php

namespace App\DataFixtures;

use App\Entity\Project;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class ProjectFixtures extends Fixture implements OrderedFixtureInterface
{
    private $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function load(ObjectManager $manager)
    {
        $project = new Project();
        $project->setStatute(1);
        $project->addUser($this->repository->find(1));
        $manager->persist($project);
        $manager->flush();

        $project = new Project();
        $project->setStatute(0);
        $project->addUser($this->repository->find(2));
        $project->addUser($this->repository->find(3));
        $manager->persist($project);
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return int
     */
    public function getOrder()
    {
        return 2;
    }
}
