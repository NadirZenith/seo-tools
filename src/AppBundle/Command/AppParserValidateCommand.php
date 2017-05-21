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

class AppParserValidateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:parser:validate')
            ->setDescription('Validate internal parsed links')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Test mode, do not save');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var LinkProcessor $processor
         */
        //        $processor = $this->getContainer()->get('app.url_parser');

        /**
         * @var EntityManager $manager
         */
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $links = $manager->getRepository(Link::class)->findBy(['status' => Link::STATUS_PARSED, 'type' => Link::TYPE_INTERNAL], ['id' => 'ASC']);

        $output->writeln(sprintf("Found %d waiting validation links", count($links)));


        define('VNU_PATH', '/data/software/vnu_html_validator/vnu.jar');

        $file = tmpfile();
        $fileMeta = stream_get_meta_data($file);
        $path = $fileMeta['uri'];

        /**
         * @var Link $link
         */
        foreach ($links as $k => $link) {
            $output->write(sprintf('%d. Start validating link id %d(%s)', ++$k, $link->getId(), $link->getUrl()));

            file_put_contents($path, $link->getResponse());

            $command = sprintf('java -jar %s --format=json %s 2>&1', VNU_PATH, $path);

            $out = array();
            exec($command, $out);
            $link->setValidation($out);

            //            $processor->validate($link, []);

            $output->writeln(sprintf(" - problems found: %d", count($link->getValidation())));

            $manager->persist($link);

            if (!$input->getOption('dry-run')) {
                $manager->flush();
            }
        }

        $output->writeln(sprintf("Links validated: %d", count($links)));
    }
}
