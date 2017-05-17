<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use AppBundle\Services\UrlParser;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Test mode, do not save');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var UrlParser $parser
         */
        $parser = $this->getContainer()->get('app.url_parser');
        /**
         * @var EntityManager $manager
         */
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $links = $manager->getRepository(Link::class)->findBy(array('status' => Link::STATUS_WAITING));

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
            $parser->parse(
                $link, [
                    'ignored_url_patterns' => [
                        //facebook
                        '/^http:\/\/www\.facebook\.com\/sharer\.php/',
                        '/^http(s)?:\/\/www\.facebook\.com/',
                        //twitter
                        '/^https:\/\/twitter\.com\/intent\/tweet/',
                        '/^http(s)?:\/\/(www\.)?twitter\.com/',
                        //google
                        '/^https:\/\/plus\.google\.com/',
                        '/^http:\/\/www\.youtube\.com/',
                        //instagram
                        '/^http:\/\/instagram\.com/',
                        //pinterest
                        '/^http:\/\/(www\.)?pinterest\.com/',
                        // special case
                        '/^https:\/\/www\.schweppes\.es/',
                    ],
                    'ignored_path_patterns' => '/^\/\_/'
                ]
            );

            $output->writeln(sprintf(" - status: %d", $link->getStatusCode()));
            $output->writeln(sprintf("Found %d new urls \n    %s \n", count($link->getChildrenUrls()), implode("\n    ", $link->getChildrenUrls())));

            $manager->persist($link);

            try {
                if (!$input->getOption('dry-run')) {
                    $manager->flush();
                }
            } catch (\Exception $e) {
                $this->writeln($e->getMessage());
                break;
            }
        }

        $output->writeln(sprintf("Finished parsing %d links", count($links)));
    }
}
