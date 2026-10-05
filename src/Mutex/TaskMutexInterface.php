<?php

declare(strict_types=1);

namespace Marko\Scheduler\Mutex;

use Marko\Scheduler\Exceptions\SchedulerException;
use Marko\Scheduler\ScheduledTask;

/**
 * Prevents a scheduled task from running while a previous run is still in progress.
 *
 * The default implementation is a local file lock. Multi-server deployments should
 * bind a distributed implementation (e.g. Redis-backed) via a Preference.
 */
interface TaskMutexInterface
{
    /**
     * Try to take the mutex for the task without blocking.
     *
     * A mutex held for longer than $expiresAfterSeconds is considered stale and is reclaimed.
     *
     * @return bool True when the mutex was acquired, false when another run holds it
     * @throws SchedulerException When the mutex cannot be stored
     */
    public function acquire(
        ScheduledTask $task,
        int $expiresAfterSeconds,
    ): bool;

    /**
     * Release a mutex previously acquired by this instance. Does nothing if it is not held.
     *
     * @throws SchedulerException When the task has no stable identity
     */
    public function release(
        ScheduledTask $task,
    ): void;

    /**
     * Whether an unexpired mutex is currently held for the task.
     *
     * @throws SchedulerException When the task has no stable identity
     */
    public function exists(
        ScheduledTask $task,
    ): bool;
}
