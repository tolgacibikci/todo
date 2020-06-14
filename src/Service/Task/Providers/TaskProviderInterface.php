<?php

namespace App\Service\Task\Providers;

/**
 * Interface for TaskProviders
 */
interface TaskProviderInterface
{
    /**
     * Get task API Url
     *
     * @return string
     */
    public function getTaskUrl();

    /**
     * Save All Tasks
     *
     * @param array $results
     *
     * @return mixed
     */
    public function saveAllTasks(array $results);
}
