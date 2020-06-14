<?php

namespace App\Service;

use App\Entity\Developer;
use App\Entity\Task;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ToDoService extends AbstractService
{
    const MATRIX_ROW = 0;
    const MATRIX_COLUMN = 1;

    /**
     * @var int Matrix height
     */
    protected $height;

    /**
     * @var int Matrix width
     */
    protected $width;
    /**
     * @var int
     */
    protected $matrixSize;

    /**
     * @var array Visited Matrix cell list
     */
    protected $visited;

    /**
     * @var \App\Repository\TaskRepository|\Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    protected $taskRepository;

    /**
     * @var \App\Repository\DeveloperRepository|\Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    protected $developerRepository;

    /**
     * ToDoService constructor.
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        parent::__construct($container, $logger);

        $this->taskRepository = $this->entityManager->getRepository(Task::class);
        $this->developerRepository = $this->entityManager->getRepository(Developer::class);
    }

    /**
     * Get developers tasks
     *
     * @return array|null
     */
    public function getAssignedTasks(): ? array
    {
        try {
            $tasks = $this->taskRepository->getAllTaskWithCost();
            if (empty($tasks)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Tasks not found.');

                return [];
            }

            $developers = $this->developerRepository->getAllDeveloperWithSkill();
            if (empty($developers)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Developers not found.');

                return [];
            }

            $taskMatrix = $this->createMatrix($developers, $tasks);
            if (empty($taskMatrix)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Matrix could not created.');

                return [];
            }

            $taskMatrix = $this->subtractSmallestValueForRow($taskMatrix);
            if (empty($taskMatrix)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Row values could not checked.');

                return [];
            }

            $taskMatrix = $this->subtractSmallestValueForColumn($taskMatrix);
            if (empty($taskMatrix)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Column values could not checked.');

                return [];
            }

            $taskMatrix = $this->checkForHeightAndWeight($taskMatrix);
            if (empty($taskMatrix)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Matrix height and width are not same.');

                return [];
            }

            $taskMatrix = $this->findBestMatrix($taskMatrix);
            if (empty($taskMatrix)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Best matrix could not found.');

                return [];
            }

            $taskMatrix = $this->unsetVirtualRowsOrColumns($taskMatrix);
            if (empty($taskMatrix)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Virtual columns or rows could not removed.');

                return [];
            }

            $assignableTasksForDevelopers = $this->findAssignableTasksForDevelopers($taskMatrix);
            if (empty($assignableTasksForDevelopers)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Assignable tasks list could not found.');

                return [];
            }

            $developersAndTasks = $this->matchTaskAndDevelopers($assignableTasksForDevelopers);
            if (empty($developersAndTasks)) {
                $this->logger->error('[ToDoService][getAssignedTasks] Developer tasks list could not found.');

                return [];
            }

            return $developersAndTasks;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][getAssignedTasks] %s', $e));
        }

        return null;
    }

    /**
     * Create matrix with developers and tasks
     *
     * @param array $developers Workers column
     * @param array $tasks Jobs column
     *
     * @return array|null
     */
    public function createMatrix(array $developers, array $tasks): ?array
    {
        try {
            $matrix = [];
            foreach ($developers as $developerKey => $developer) {
                foreach ($tasks as $taskKey => $task) {
                    $matrix[$developerKey][$taskKey] = $task['cost'] / $developer['skill'];
                    //$matrix[$task['id']][$developer['id']] = $task['cost'] / $developer['skill'];
                }
            }

            return $matrix;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][createMatrix] %s', $e));
        }

        return null;
    }

    /** Find the smallest value for the row and subtract this value from the values of row.
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function subtractSmallestValueForRow(array $matrix): ?array
    {
        try {
            foreach ($matrix as $rowKey => $rowValues) {
                $minValueForRow = min($rowValues);

                foreach ($rowValues as $columnKey => $rowValue) {
                    $matrix[$rowKey][$columnKey] = $rowValue - $minValueForRow;
                }
            }

            return $matrix;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][subtractSmallestValueForRow] %s', $e));
        }

        return null;
    }

    /** Find the smallest value for the column and subtract this value from the values of column.
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function subtractSmallestValueForColumn(array $matrix): ?array
    {
        try {
            $smallestValueForEachColumn = $this->findSmallestValueForEachColumn($matrix);

            if (empty($smallestValueForEachColumn)) {
                $this->logger->error(
                    '[ToDoService][subtractSmallestValueForRow] Smallest values could not found for columns.'
                );

                return null;
            }

            foreach ($matrix as &$rowValues) {
                foreach ($rowValues as $key => $rowValue) {
                    $rowValues[$key] = $rowValue - $smallestValueForEachColumn[$key];
                }
            }

            return $matrix;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][subtractSmallestValueForRow] %s', $e));
        }

        return null;
    }

    /**
     * Find smallest value for each column
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function findSmallestValueForEachColumn(array $matrix): ?array
    {
        try {
            $smallestValueForEachColumn = [];
            foreach ($matrix as $rowKey => $rowValues) {
                foreach ($rowValues as $columnKey => $columnValue) {
                    if (!array_key_exists($columnKey, $smallestValueForEachColumn)) {
                        $smallestValueForEachColumn[$columnKey] = $columnValue;
                    } elseif ($smallestValueForEachColumn[$columnKey] > $columnValue) {
                        $smallestValueForEachColumn[$columnKey] = $columnValue;
                    }
                }
            }

            return $smallestValueForEachColumn;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][findSmallestValueForEachColumn] %s', $e));
        }

        return null;
    }

    /**
     * Check for number of developer and number of tasks.
     * If it is not equals each other create fake rows or column.
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function checkForHeightAndWeight(array $matrix): ? array
    {
        try {
            $this->height = count($matrix);
            $this->width = count($matrix[array_key_first($matrix)]);

            if ($this->height < $this->width) {
                for ($i = $this->height; $i < $this->width; ++$i) {
                    $matrix[$i] = array_fill(0, $this->width, 0);
                }
            } elseif ($this->width < $this->height) {
                foreach ($matrix as &$row) {
                    for ($i = $this->width; $i < $this->height; ++$i) {
                        $row[$i] = 0;
                    }
                }
            }

            if (count($matrix) && count($matrix[array_key_first($matrix)])) {
                $this->matrixSize = count($matrix);

                return $matrix;
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][checkForHeightAndWeight] %s', $e));
        }

        return null;
    }

    /**
     * Find best matrix
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function findBestMatrix(array $matrix): ?array
    {
        try {
            $zeroNumberForRows = $this->findZeroNumberForRows($matrix);
            if (empty($zeroNumberForRows)) {
                $this->logger->error('[ToDoService][findBestMatrix] Zero number could not calculated for rows.');

                return null;
            }

            $zeroNumberForColumns = $this->findZeroNumberForColumns($matrix);
            if (empty($zeroNumberForRows)) {
                $this->logger->error('[ToDoService][findBestMatrix] Zero number could not calculated for columns.');

                return null;
            }

            $lineNumber = 0;
            while ($lineNumber <= $this->matrixSize) {
                $remove = [];

                if (!empty($zeroNumberForRows) && !empty($zeroNumberForColumns)) {
                    foreach ($zeroNumberForRows as $rowKey => $rowZeroNumber) {
                        foreach ($zeroNumberForColumns as $columnKey => $columnZeroNumber) {
                            if ($rowZeroNumber > $columnZeroNumber) {
                                $remove = ['from' => self::MATRIX_ROW, 'key' => $rowKey];
                            } else {
                                $remove = ['from' => self::MATRIX_COLUMN, 'key' => $columnKey];
                            }
                        }
                    }
                } elseif (empty($zeroNumberForRows)) {
                    foreach ($zeroNumberForColumns as $columnKey => $columnZeroNumber) {
                        if (empty($remove)) {
                            $remove = ['from' => self::MATRIX_COLUMN, 'key' => $columnKey];
                        } elseif ($columnZeroNumber > $zeroNumberForColumns[$remove['key']]) {
                            $remove = ['from' => self::MATRIX_COLUMN, 'key' => $columnKey];
                        }
                    }
                } elseif (empty($zeroNumberForColumns)) {
                    foreach ($zeroNumberForRows as $rowKey => $rowZeroNumber) {
                        if (empty($remove)) {
                            $remove = ['from' => self::MATRIX_ROW, 'key' => $rowKey];
                        } elseif ($rowZeroNumber > $zeroNumberForRows[$remove['key']]) {
                            $remove = ['from' => self::MATRIX_ROW, 'key' => $rowKey];
                        }
                    }
                }

                $matrix = $this->markAsVisited($matrix, $remove['from'], $remove['key']);

                if (empty($matrix)) {
                    $this->logger->error('[ToDoService][findBestMatrix] Unexpected error.');

                    return null;
                }

                if ($remove['from'] == self::MATRIX_ROW) {
                    unset($zeroNumberForRows[$remove['key']]);

                    foreach ($zeroNumberForColumns as $key => $zeroNumberForColumn) {
                        if ($zeroNumberForColumn > 0) {
                            $zeroNumberForColumns[$key] -= 1;
                        }
                    }
                } else {
                    unset($zeroNumberForColumns[$remove['key']]);

                    foreach ($zeroNumberForRows as $key => $zeroNumberForRow) {
                        if ($zeroNumberForRow > 0) {
                            $zeroNumberForRows[$key] -= 1;
                        }
                    }
                }

                $lineNumber++;
            }

            return $matrix;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][findBestMatrix] %s', $e));
        }

        return null;
    }

    /**
     * Find zero number for each rows
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function findZeroNumberForRows(array $matrix): ?array
    {
        try {
            $zeroNumberForRows = array_fill(0, $this->matrixSize, 0);

            foreach ($matrix as $rowKey => $rowValues) {
                foreach ($rowValues as $columnKey => $columnValue) {
                    if ($columnValue == 0) {
                        $zeroNumberForRows[$rowKey] += 1;
                    }
                }
            }

            return $zeroNumberForRows;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][findZeroNumberForRows] %s', $e));
        }

        return null;
    }

    /**
     * Find zero number for each columns
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function findZeroNumberForColumns(array $matrix): ?array
    {
        try {
            $row = $this->matrixSize;
            $column = $row;

            $zeroNumberForColumns = array_fill(0, $this->matrixSize, 0);

            for ($columnNumber = 0; $columnNumber < $column; $columnNumber++) {
                for ($rowNumber = 0; $rowNumber < $column; $rowNumber++) {
                    if ($matrix[$rowNumber][$columnNumber] == 0) {
                        $zeroNumberForColumns[$columnNumber] += 1;
                    }
                }
            }

            return $zeroNumberForColumns;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][findZeroNumberForColumns] %s', $e));
        }

        return null;
    }

    /**
     * Mark as visit row or column
     *
     * @param array $matrix Developer and task array
     * @param int $visitType Visit row or column
     * @param int $visitKey Visit key
     *
     * @return array|null
     */
    public function markAsVisited(array $matrix, int $visitType, int $visitKey): ?array
    {
        try {
            if (empty($this->visited)) {
                $this->visited = $this->copyMatrix($matrix, 0);
            }

            if ($visitType == self::MATRIX_ROW) {
                for ($columnKey = 0; $columnKey < $this->matrixSize; $columnKey++) {
                    $this->visited[$visitKey][$columnKey] += 1;
                }
            } else {
                for ($rowKey = 0; $rowKey < $this->matrixSize; $rowKey++) {
                    $this->visited[$rowKey][$visitKey] += 1;
                }
            }

            return $matrix;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][markAsVisited] %s', $e));
        }

        return null;
    }

    /**
     * Create copy of current matrix with same height and width
     *
     * @param array $matrix
     * @param int|null $value
     *
     * @return array|null
     */
    public function copyMatrix(array $matrix, ?int $value): ?array
    {
        $copyArray = [];
        foreach ($matrix as $rowKey => $rowValues) {
            foreach ($rowValues as $columnKey => $columnValue) {
                $copyArray[$rowKey][$columnKey] = $value;
            }
        }

        return $copyArray;
    }

    /**
     * Remove virtual rows or columns
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function unsetVirtualRowsOrColumns(array $matrix): ?array
    {
        try {
            if ($this->height < $this->width) {
                for ($rowNumber = $this->height; $rowNumber < $this->width; $rowNumber++) {
                    unset($matrix[$rowNumber]);
                }
            } elseif ($this->width < $this->height) {
                for ($columnNumber = $this->width; $columnNumber < $this->height; $columnNumber++) {
                    for ($rowNumber = 0; $rowNumber < $this->height; $rowNumber++) {
                        unset($matrix[$rowNumber][$columnNumber]);
                    }
                }
            }

            return $matrix;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][unsetVirtualRowsOrColumns] %s', $e));
        }

        return null;
    }

    /**
     * Find Assignable tasks for developers
     *
     * @param array $matrix
     *
     * @return array|null
     */
    public function findAssignableTasksForDevelopers(array $matrix): ?array
    {
        try {
            $developersTasks = [];
            foreach ($matrix as $rowKey => $rowValues) {
                foreach ($rowValues as $columnKey => $columnValue) {
                    if ($columnValue == 0) {
                        $developersTasks[$rowKey][] = $columnKey;
                    }
                }
            }

            $tasksDevelopers = [];
            for ($columnNumber = 0; $columnNumber < $this->width; $columnNumber++) {
                for ($rowNumber = 0; $rowNumber < $this->height; $rowNumber++) {
                    if ($matrix[$rowNumber][$columnNumber] == 0) {
                        $tasksDevelopers[$columnNumber][] = $rowNumber;
                    }
                }
            }

            $assignedList = $this->assignTasksToDevelopers($matrix, $developersTasks, $tasksDevelopers);
            if (empty($assignedList)) {
                $this->logger->error('[ToDoService][findAssignableTasksForDevelopers] Assigned list not found');

                return null;
            }

            return $assignedList;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][findAssignableTasksForDevelopers] %s', $e));
        }

        return null;
    }

    /**
     * Assign tasks for developers
     *
     * @param array $matrix
     * @param array $developersTasks
     * @param array $tasksDevelopers
     *
     * @return array|null
     */
    public function assignTasksToDevelopers(array $matrix, array $developersTasks, array $tasksDevelopers): ?array
    {
        try {
            $assignedList = $this->copyMatrix($matrix, null);

            for ($taskKey = 0; $taskKey < $this->width; $taskKey++) {
                if (count($tasksDevelopers[$taskKey]) == 1) {
                    $assignedList[$tasksDevelopers[$taskKey][0]][$taskKey] = true;

                    unset($developersTasks[$tasksDevelopers[$taskKey][0]]);

                    unset($tasksDevelopers[$taskKey]);

                }
            }

            for ($developerKey = 0; $developerKey < count($developersTasks); $developerKey++) {
                if (count($developersTasks[$developerKey]) == 1) {
                    $assignedList[$developerKey][$developersTasks[$developerKey][0]] = true;

                    unset($developersTasks[$developerKey]);
                }
            }

            foreach ($tasksDevelopers as $taskKey => $taskDevelopers) {
                foreach ($taskDevelopers as $taskDeveloperKey) {
                    if (isset($developersTasks[$taskDeveloperKey])
                        && in_array($taskKey, $developersTasks[$taskDeveloperKey])) {
                        $assignedList[$taskDeveloperKey][$taskKey] = true;

                        unset($developersTasks[$taskDeveloperKey]);
                        unset($tasksDevelopers[$taskKey]);
                        break;
                    }
                }
            }

            return $assignedList;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][assignTasksToDevelopers] %s', $e));
        }

        return null;
    }

    /**
     * Create developers task list with names
     *
     * @param array $assignableTasksForDevelopers
     *
     * @return array|null
     */
    public function matchTaskAndDevelopers(array $assignableTasksForDevelopers): ? array
    {
        try {
            $developers = $this->developerRepository->getAllDevelopersIdAndName();
            if (empty($developers)) {
                $this->logger->error('[ToDoService][matchTaskAndDevelopers] Developers not found.');

                return [];
            }

            return $assignableTasksForDevelopers;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[ToDoService][matchTaskAndDevelopers] %s', $e));
        }

        return null;
    }
}
