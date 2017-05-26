<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use AppBundle\Services\LinkProcessor;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppParserParseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:parser:parse')
            ->setDescription('Parse waiting links')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id from link to see status')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Test mode, do not save')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Does not throw exception');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        /**
         * @var LinkProcessor $processor
         */
        $processor = $this->getContainer()->get('app.link_processor');
        /**
         * @var EntityManager $manager
         */
        $manager = $this->getContainer()->get('doctrine')->getManager();

        $links = $this->getLinks($input->getArgument('id'), $manager);

        if (!$links) {
            $output->writeln('No waiting links');
            return;
        }
        $output->writeln(sprintf("Found %d waiting links", count($links)));

        /**
         * @var Link $link
         */
        foreach ($links as $k => $link) {
            $output->write(sprintf('%d/%d -> Start parsing url %s', ++$k, count($links), $link->getUrl()));
            $status = $processor->process($link, [
                    'force'                 => $input->getOption('force'),
                    'ignored_url_patterns'  => [
                        //facebook
                        '/^http:\/\/www\.facebook\.com\/sharer\.php/',
                        '/^http(s)?:\/\/www\.facebook\.com/',
                        //twitter
                        '/^https:\/\/twitter\.com\/intent\/tweet/',
                        '/^http(s)?:\/\/(www\.)?twitter\.com/',
                        //google
                        '/^https:\/\/plus\.google\.com/',
                        '/^http(s)?:\/\/www\.youtube\.com/',
                        //instagram
                        '/^http:\/\/instagram\.com/',
                        //soundcloud
                        '/^http(s)?:\/\/(www\.)?soundcloud\.com/',
                        //pinterest
                        '/^http:\/\/(www\.)?pinterest\.com/',
                        // special case
                    ],
                    'ignored_path_patterns' => ['/^\/\_/']
                ]
            );

            $output->writeln(sprintf(" - status: %d", $link->getStatusCode()));

            if ($status) {
                if (Link::TYPE_EXTERNAL === $link->getType()) {
                    $output->writeln(sprintf("    Type %s\n", Link::TYPE_EXTERNAL));
                }
                if (Link::TYPE_EXTERNAL !== $link->getType()) {
                    $output->writeln(sprintf("    Found %d new urls: \n        %s\n", count($link->getChildrenUrls()), implode("\n        ", $link->getChildrenUrls())));
                }
            }

            if (!$status) {
                $output->writeln(sprintf("    Status message: %s\n", $link->getStatusMessage()));
            }

            $manager->persist($link);

            try {
                if (!$input->getOption('dry-run')) {
                    $manager->flush();
                }
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                break;
            }
        }

        $output->writeln(sprintf("Finished parsing %d links", count($links)));
    }

    private function getLinks($id, EntityManager $manager)
    {
        if ($id) {
            return $manager->getRepository(Link::class)->findBy(['id' => $id]);
        }

        return $manager->getRepository(Link::class)->findBy(['status' => Link::STATUS_WAITING]);
    }
}
