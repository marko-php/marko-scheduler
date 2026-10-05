<?php

declare(strict_types=1);

namespace Marko\Scheduler\Exceptions;

use Marko\Core\Exceptions\MarkoException;

class SchedulerException extends MarkoException
{
    public static function overlapRequiresDescription(
        string $expression,
    ): self {
        return new self(
            message: "A scheduled task with expression '$expression' uses withoutOverlapping() but has no description.",
            context: 'While building the overlap mutex name for a scheduled task',
            suggestion: "Closures have no stable identity, so overlap protection keys the mutex on the task's description. Add ->description('Unique task name') to the task.",
        );
    }

    public static function invalidOverlapExpiry(
        int $expiresAfterMinutes,
    ): self {
        return new self(
            message: "withoutOverlapping() expiry must be at least 1 minute, $expiresAfterMinutes given.",
            context: 'While configuring overlap protection for a scheduled task',
            suggestion: 'Pass the number of minutes after which a held mutex is considered stale, e.g. ->withoutOverlapping(60). The default is 1440 (24 hours).',
        );
    }

    public static function mutexDirectoryNotWritable(
        string $directory,
    ): self {
        return new self(
            message: "Scheduler mutex directory '$directory' could not be created or is not writable.",
            context: 'While acquiring a scheduled task overlap mutex',
            suggestion: "Create the directory and make it writable by the user running the scheduler, e.g. mkdir -p '$directory'.",
        );
    }

    public static function mutexFileNotOpenable(
        string $path,
    ): self {
        return new self(
            message: "Scheduler mutex file '$path' could not be opened.",
            context: 'While acquiring a scheduled task overlap mutex',
            suggestion: 'Check the permissions of the mutex directory and file for the user running the scheduler.',
        );
    }
}
