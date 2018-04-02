<?php

namespace AppBundle\Command;

use AppBundle\Entity\Link;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;

abstract class AppBaseParserCommand extends ContainerAwareCommand
{

    /**
     * @param InputInterface $input
     * @param EntityManager $manager
     * @return \AppBundle\Entity\Link[]|array
     */
    protected function getLinks(InputInterface $input, EntityManager $manager)
    {
        $id = $input->getArgument('id');

        if ($id) {
            return $manager->getRepository(Link::class)->findBy(['id' => $id]);
        }

        $filter = [
            'status' => $input->getOption('status'),
            'type' => $input->getOption('type'),
        ];

        return $manager->getRepository(Link::class)->findBy($filter, null, (int)$input->getOption('limit'));
    }

    protected function getParserOptions(InputInterface $input)
    {
        return [
            'force' => $input->getOption('force'),
            'ignored_url_patterns' => [
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
        ];
    }
}
