<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppParserStatusCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:parser:status')
            ->setDescription('See links global status')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $waiting = $manager->getRepository(Link::class)->findBy(array('status' => Link::STATUS_WAITING));
        $parsed = $manager->getRepository(Link::class)->findBy(array('status' => Link::STATUS_PARSED));

        $output->writeln(sprintf("Waiting parser: %d links", count($waiting)));
        $output->writeln(sprintf("Already parsed: %d links", count($parsed)));
    }

}
