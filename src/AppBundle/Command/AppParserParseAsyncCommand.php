<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use AppBundle\Services\LinkProcessor;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppParserParseAsyncCommand extends AppBaseParserCommand
{

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('app:parser:parse-async')
            ->setDescription('Parse waiting links asynchronously')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id from link to see status')
            ->addOption('status', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Filter links by status', [Link::STATUS_WAITING])
            ->addOption('type', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Filter links by type', [Link::TYPE_INTERNAL])
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Pager limit', 10)
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

        while ($links = $this->getLinks($input, $manager)) {
//            if (!$links) {
//                $output->writeln('No waiting links');
//                break;
//            }
            $output->writeln(sprintf("Start batch: %d links", count($links)));

            $return = $processor->processAsync($links, $this->getParserOptions($input), function (Link $link, $i, \Exception $error = null) use ($input, $output, $manager, $links) {
                $output->write(sprintf('%d/%d -> Url %s', ++$i, count($links), $link->getUrl()));

                if ($error) {
                    $output->writeln(sprintf("    Status message: %s\n Error message %s\n", $link->getStatusMessage(), $error->getMessage()));
                }

                if (!$error) {
                    $output->writeln(sprintf("    - status: %s", $link->getStatusCode()));
                    if (Link::TYPE_EXTERNAL === $link->getType()) {
                        $output->writeln(sprintf("    Type %s\n", Link::TYPE_EXTERNAL));
                    }
                    if (Link::TYPE_EXTERNAL !== $link->getType()) {
                        $output->writeln(sprintf("    Found %d new urls: \n        %s\n", count($link->getChildrenUrls()), implode("\n        ", $link->getChildrenUrls())));
                    }
                }

                try {
                    if (!$input->getOption('dry-run')) {
                        $manager->persist($link);
                        $manager->flush();
                    }

                    // $manager->detach($link); // new entity in relation exceptions
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                }

            }, function () use ($output, $links) {
                $output->writeln(sprintf("End request batch: %d links", count($links)));
            });

            if ($input->getOption('dry-run')) {
                break;
            }

            $manager->clear(Link::class);

            $output->writeln(sprintf("Next batch -------"));
        }
    }
}
