<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use AppBundle\Services\UrlParser;
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
        /** @var UrlParser $parser */
        $parser = $this->getContainer()->get('app.url_parser');

        /** @var EntityManager $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $links = $manager->getRepository(Link::class)->findBy(['status' => Link::STATUS_PARSED, 'type' => Link::TYPE_INTERNAL], ['id' => 'ASC']);
        define('VNU_PATH', '/data/software/vnu_html_validator/vnu.jar');

        $file = tmpfile();
        $file_meta = stream_get_meta_data($file);
        $path = $file_meta['uri']; // eg: /tmp/phpFx0513a
        /** @var Link $link */
        foreach ($links as $k => $link) {

            $output->writeln(sprintf('%d. Start validating link id %d(%s)', ++$k, $link->getId(), $link->getUrl()));

            $result = file_put_contents($path, $link->getResponse());

            $command = sprintf('java -jar %s --format=json %s 2>&1', VNU_PATH, $path);
//            $command = sprintf('java -jar %s %s 2>&1', VNU_PATH, $path);

            $r = exec($command, $out, $return);
            $link->setValidation($out);

//            $parser->validate($link, []);

//            $output->writeln(sprintf(" - status: %d", $link->getStatusCode()));
//            $output->writeln(sprintf("Found %d new urls \n    %s \n", count($link->getChildrenUrls()), implode("\n    ", $link->getChildrenUrls())));

            $manager->persist($link);

            if (!$input->getOption('dry-run')) {
                $manager->flush();
            }
        }

        $output->writeln(sprintf("Links needing validation: %d", count($links)));
    }

}
