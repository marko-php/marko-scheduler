<?php

declare(strict_types=1);

use Marko\Core\Container\ContainerInterface;
use Marko\Core\Path\ProjectPaths;
use Marko\Scheduler\Mutex\FileTaskMutex;
use Marko\Scheduler\Mutex\TaskMutexInterface;
use Marko\Scheduler\Schedule;

return [
    'bindings' => [
        TaskMutexInterface::class => function (ContainerInterface $container): TaskMutexInterface {
            return new FileTaskMutex(
                directory: $container->get(ProjectPaths::class)->base . '/storage/framework',
            );
        },
    ],
    'singletons' => [
        Schedule::class,
        TaskMutexInterface::class,
    ],
];
