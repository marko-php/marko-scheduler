<?php

declare(strict_types=1);

namespace Marko\Scheduler\Command;

use DateTimeImmutable;
use Marko\Core\Attributes\Command;
use Marko\Core\Command\CommandInterface;
use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Scheduler\Exceptions\SchedulerException;
use Marko\Scheduler\ScheduleRunner;

/** @noinspection PhpUnused */
#[Command(name: 'schedule:run', description: 'Run due scheduled tasks')]
readonly class RunScheduleCommand implements CommandInterface
{
    public function __construct(
        private ScheduleRunner $scheduleRunner,
    ) {}

    /**
     * @return int 0 when every due task succeeded or was skipped, 1 when any task failed
     * @throws SchedulerException
     */
    public function execute(
        Input $input,
        Output $output,
    ): int {
        $result = $this->scheduleRunner->run(new DateTimeImmutable(), $output);

        return $result->hasFailures() ? 1 : 0;
    }
}
