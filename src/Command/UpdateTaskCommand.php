<?php

namespace App\Command;

use App\Service\Task\Providers\TaskOneProvider;
use App\Service\Task\Providers\TaskProviderInterface;
use App\Service\Task\Providers\TaskTwoProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class UpdateTaskCommand extends Command
{
    /**
     * Command Name
     *
     * @var string
     */
    protected static $defaultName = 'task:update';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TaskOneProvider
     */
    protected $taskOneProvider;

    /**
     * @var TaskTwoProvider
     */
    protected $taskTwoProvider;

    /**
     * UpdateTaskCommand constructor.
     *
     * @param LoggerInterface $logger
     * @param TaskOneProvider $taskOneProvider
     * @param TaskTwoProvider $taskTwoProvider
     */
    public function __construct(
        LoggerInterface $logger,
        TaskOneProvider $taskOneProvider,
        TaskTwoProvider $taskTwoProvider
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->taskOneProvider = $taskOneProvider;
        $this->taskTwoProvider = $taskTwoProvider;
    }

    protected function configure()
    {
        $this
            ->setDescription('This command fetch all new tasks')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            /** @var TaskProviderInterface[] $providers */
            $allTaskProviders = [$this->taskOneProvider, $this->taskTwoProvider];

            foreach ($allTaskProviders as $taskProvider) {
                $client = HttpClient::create();

                $response = $client->request('GET', $taskProvider->getTaskUrl());

                if (($response instanceof  ResponseInterface) && $response->getStatusCode() == Response::HTTP_OK) {
                    $result = $taskProvider->saveAllTasks($response->toArray());

                    if ($result) {
                        $io->success(
                            sprintf(
                                '[UpdateTaskCommand][execute] Results are saved for %s',
                                get_class($taskProvider)
                            )
                        );
                    } else {
                        $errorMessage = sprintf(
                            '[UpdateTaskCommand][execute] Results could not saved for %s',
                            get_class($taskProvider)
                        );

                        $io->error($errorMessage);

                        $this->logger->error($errorMessage, [
                            'response' => $response,
                        ]);
                    }
                } else {
                    $errorMessage = sprintf(
                        '[UpdateTaskCommand][execute] Request not successful for %s',
                        get_class($taskProvider)
                    );

                    $io->error($errorMessage);

                    $this->logger->error($errorMessage, [
                        'response' => $response,
                    ]);
                }
            }

            $io->success('All tasks have been saved.');
        } catch (\Throwable $e) {
            $errorMessage = '[UpdateTaskCommand][execute] An unexpected error has occurred.';

            $io->error($errorMessage);

            $this->logger->error(sprintf('%s %s', $errorMessage, $e));
        }
    }
}
