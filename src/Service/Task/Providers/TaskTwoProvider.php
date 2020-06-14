<?php

namespace App\Service\Task\Providers;

use App\Entity\Task;
use App\Service\AbstractProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskTwoProvider extends AbstractProvider implements TaskProviderInterface
{
    /**
     * @var string API URL
     */
    const TASK_URL = 'http://www.mocky.io/v2/5d47f235330000623fa3ebf7';

    /**
     * TaskTwoProvider constructor.
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
            foreach ($result as $key => $taskItem) {
                $task = new Task();
                $task->setName(array_key_first($taskItem));
                $task->setTime(array_values($taskItem)[0]['estimated_duration']);
                $task->setDifficulty(array_values($taskItem)[0]['level']);

                $this->entityManager->persist($task);
            }

            $this->entityManager->flush();

            return true;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[TaskTwoProvider][saveAllTasks] %s', $e));
        }

        return null;
    }
}
