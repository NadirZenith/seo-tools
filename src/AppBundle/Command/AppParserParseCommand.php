<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use AppBundle\Services\LinkProcessor;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppParserParseCommand extends AppBaseParserCommand
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
            ->addOption('status', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Filter links by status', [Link::STATUS_WAITING])
            ->addOption('type', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Filter links by type', [Link::TYPE_INTERNAL])
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

//        $links = $this->getLinks($input->getArgument('id'), $input->getOption('limit'), $manager);
        $links = $this->getLinks($input, $manager);

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

            $status = $processor->process($link, $this->getParserOptions($input));

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

            try {
                if (!$input->getOption('dry-run')) {
                    $manager->persist($link);
                    $manager->flush();
                }

                // $manager->detach($link); // new entity in relation exceptions
            } catch (\Exception $e) {

                $output->writeln($e->getMessage());
                break;
            }
        }

        $output->writeln(sprintf("Finished parsing %d links", count($links)));
    }
}
