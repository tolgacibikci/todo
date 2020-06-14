<?php

namespace App\Service\Task\Providers;

use App\Entity\Task;
use App\Service\AbstractProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskOneProvider extends AbstractProvider implements TaskProviderInterface
{
    /**
     * @var string API URL
     */
    const TASK_URL = 'http://www.mocky.io/v2/5d47f24c330000623fa3ebfa';

    /**
     * TaskOneProvider constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        parent::__construct($container, $logger);
    }

    /**
     * Get task API Url
     *
     * @return string
     */
    public function getTaskUrl(): string
    {
        return self::TASK_URL;
    }

    /**
     * Save All Tasks
     *
     * @param array $result
     *
     * @return bool|null
     */
    public function saveAllTasks(array $result): ?bool
    {
        try {
            foreach ($result as $taskItem) {
                $task = new Task();
                $task->setName($taskItem['id']);
                $task->setTime($taskItem['sure']);
                $task->setDifficulty($taskItem['zorluk']);

                $this->entityManager->persist($task);
            }

            $this->entityManager->flush();

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[TaskOneProvider][saveAllTasks] %s', $e));
        }

        return null;
    }
}
