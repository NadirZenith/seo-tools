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
                $roots = $manager->getRepository(Link::class)
                    ->createQueryBuilder('l')
                    ->where('l.parent = :id and l.status = :status')
                    ->setParameter('id', $id)
                    ->setParameter('status', Link::STATUS_WAITING)
                    ->getQuery()->getResult();

                foreach ($roots as $link) {
                    $output->writeln(sprintf("#%d %s", $link->getId(), $link->getUrl()));
                }

                return;
            }

            $roots = $manager->getRepository(Link::class)
                ->createQueryBuilder('l')
                ->where("l.status = :status")
                ->setParameter('status', Link::STATUS_WAITING)
                ->getQuery()->getResult();

            foreach ($roots as $link) {
                $output->writeln(sprintf("#%d %s", $link->getId(), $link->getUrl()));
            }

            return;
        }

        $root = (int)$input->getArgument('id');

        $output->writeln(sprintf("Waiting parser: %d links", $this->getLinksCount(['status' => Link::STATUS_WAITING], $manager, $root)));
        $output->writeln(sprintf("Waiting parser internal: %d links", $this->getLinksCount(['status' => Link::STATUS_WAITING, 'type' => Link::TYPE_INTERNAL], $manager, $root)));
        $output->writeln(sprintf("Already parsed: %d links", $this->getLinksCount(['status' => Link::STATUS_PARSED], $manager, $root)));
        $output->writeln(sprintf("Skipped parser: %d links", $this->getLinksCount(['status' => Link::STATUS_SKIPPED], $manager, $root)));
    }

    /**
     * @param $filter
     * @param EntityManager $manager
     * @param $root
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function getLinksCount($filter, EntityManager $manager, $root)
    {
        $qb = $manager->getRepository(Link::class)
            ->createQueryBuilder('l')
            ->select('count(l.id)');

        $filter = array_merge([
            'status' => Link::STATUS_WAITING
        ], (array)$filter);

        if (isset($filter['status'])) {
            $qb
                ->andWhere('l.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if (isset($filter['type'])) {
            $qb
                ->andWhere('l.type = :type')
                ->setParameter('type', $filter['type']);
        }

        if ($root) {
            $qb->andWhere('l.root = :root')->setParameter('root', $root);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
