<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractService
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Doctrine\ORM\EntityManager|object
     */
    protected $entityManager;

    /**
     * AbstractService constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->entityManager = $container->get('doctrine.orm.entity_manager');
    }
}
