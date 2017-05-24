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
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('app:parser:status')
            ->setDescription('See links status')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id from root link to see status')
            ->addOption('list', null, InputOption::VALUE_NONE, 'List root links');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var EntityManager $manager
         */
        $manager = $this->getContainer()->get('doctrine')->getManager();

        if ($input->getOption('list')) {
            if ($id = (int)$input->getArgument('id')) {
                $roots = $manager->getRepository(Link::class)->createQueryBuilder('l')->where('l.parent = :id and l.status = \'waiting\'')->setParameter('id', $id)->getQuery()->getResult();

                foreach ($roots as $link) {
                    $output->writeln(sprintf("#%d %s", $link->getId(), $link->getUrl()));
                }

                return;
            }

            $roots = $manager->getRepository(Link::class)->createQueryBuilder('l')->where('l.status = \'waiting\'')->getQuery()->getResult();

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

    /**
     * @param EntityManager $manager
     * @param $status
     * @param null $id
     * @return mixed
     */
    private function getLinksCount(EntityManager $manager, $status, $id = null)
    {
        $queryBuilder = $manager->getRepository(Link::class)->createQueryBuilder('l')->select('count(l.id)')->where('l.status = :status')->setParameter('status', $status);
        if ($id) {
            $queryBuilder->andWhere('l.root = :root')->setParameter('root', $id);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
