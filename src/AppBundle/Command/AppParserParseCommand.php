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

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('app:parser:parse')
            ->setDescription('Parse waiting links')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id from link to see status')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Pager limit', 100)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Test mode, do not save')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Does not throw exception');
    }

    /**
     * @inheritdoc
     */
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

        $links = $this->getLinks($input->getArgument('id'), $input->getOption('limit'), $manager);

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
                        '/^http(s)?:\/\/([a-z0-9]*\.)?facebook\.com/',
                        '/^http(s)?:\/\/(www\.)?twitter\.com/',
                        '/^http(s)?:\/\/([a-z0-9]*\.)?google\.com/',
                        '/^http(s)?:\/\/(www\.)?youtube\.com/',
                        '/^http(s)?:\/\/(www\.)?instagram\.com/',
                        '/^http(s)?:\/\/(www\.)?soundcloud\.com/',
                        '/^http(s)?:\/\/(www\.)?mixcloud\.com/',
                        '/^http(s)?:\/\/(www\.)?pinterest\.com/',
                        '/^http(s)?:\/\/(www\.)?tumblr\.com/',
                        '/^http(s)?:\/\/(www\.)?flickr\.com/',
                        '/^http(s)?:\/\/(www\.)?beatport\.com/',
                        '/^http(s)?:\/\/(www\.)?linkedin\.com/',
                        '/^http(s)?:\/\/([a-z0-9]*\.)?wikipedia\.org/',
                        '/http(s)?:\/\/([a-z0-9]*\.)?bandcamp\.com/',
                        // special cases (cm|tm|etc)
                        '/\?q\=\//',
                        '/\?date\=/',
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

    /**
     * @param int $id
     * @param int $limit
     * @param EntityManager $manager
     * @return \AppBundle\Entity\Link[]|array
     */
    private function getLinks($id, $limit, EntityManager $manager)
    {
        if ($id) {
            return $manager->getRepository(Link::class)->findBy(['id' => $id]);
        }

        return $manager->getRepository(Link::class)->findBy(['status' => Link::STATUS_WAITING], null, (int)$limit);
    }
}
