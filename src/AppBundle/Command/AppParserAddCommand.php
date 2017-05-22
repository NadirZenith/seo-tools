<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppParserAddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:parser:add')
            ->setDescription('Add url to parser table')
            ->addArgument('url', InputArgument::REQUIRED, 'Url to add');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $link = new Link($input->getArgument('url'));

        $manager = $this->getContainer()->get('doctrine')->getManager();

        $manager->persist($link);
        $manager->flush();

        $output->writeln(sprintf("New link added with id: %d(%s)", $link->getId(), $link->getUrl()));
    }
}
