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
            ->setDescription('See links status')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id from root link to see status')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List root links');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var EntityManager $manager
         */
        $manager = $this->getContainer()->get('doctrine')->getManager();

        if ($input->getOption('list')) {
            $roots = $manager->getRepository(Link::class)->createQueryBuilder('l')->where('l.parent IS NULL')->getQuery()->getResult();

            foreach ($roots as $link) {
                $output->writeln(sprintf("#%d %s", $link->getId(), $link->getUrl()));
            }

            return;
        }

        $id = (int)$input->getArgument('id');

        $output->writeln(sprintf("Waiting parser: %d links", $this->getLinksCount($manager, Link::STATUS_WAITING, $id)));
        $output->writeln(sprintf("Already parsed: %d links", $this->getLinksCount($manager, Link::STATUS_PARSED, $id)));
        $output->writeln(sprintf("Skipped parser: %d links", $this->getLinksCount($manager, Link::STATUS_SKIPPED, $id)));
    }

    private function getLinksCount(EntityManager $manager, $status, $id = null)
    {
        $queryBuilder = $manager->getRepository(Link::class)->createQueryBuilder('l')->select('count(l.id)')->where('l.status = :status')->setParameter('status', $status);
        if ($id) {
            $queryBuilder->andWhere('l.root = :root')->setParameter('root', $id);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
