<?php

declare(strict_types=1);

use Marko\Scheduler\Exceptions\SchedulerException;
use Marko\Scheduler\ScheduledTask;

it('does not prevent overlapping by default', function (): void {
    $task = new ScheduledTask(fn (): null => null);

    expect($task->preventsOverlapping())->toBeFalse();
});

it('prevents overlapping with a default expiry of 1440 minutes', function (): void {
    $task = new ScheduledTask(fn (): null => null);

    $result = $task->withoutOverlapping();

    expect($result)->toBe($task)
        ->and($task->preventsOverlapping())->toBeTrue()
        ->and($task->getOverlapExpiresAfterMinutes())->toBe(1440);
});

it('accepts a custom overlap expiry in minutes', function (): void {
    $task = new ScheduledTask(fn (): null => null);

    $task->withoutOverlapping(30);

    expect($task->getOverlapExpiresAfterMinutes())->toBe(30);
});

it('rejects an overlap expiry below one minute', function (): void {
    $task = new ScheduledTask(fn (): null => null);

    $task->withoutOverlapping(0);
})->throws(SchedulerException::class, 'at least 1 minute');

it('builds a stable mutex name from the expression and description', function (): void {
    $first = new ScheduledTask(fn (): null => null)->hourly()->description('Sync')->withoutOverlapping();
    $second = new ScheduledTask(fn (): string => 'other')->withoutOverlapping()->description('Sync')->hourly();
    $different = new ScheduledTask(fn (): null => null)->daily()->description('Sync')->withoutOverlapping();

    expect($first->mutexName())->toBe('schedule-' . sha1('0 * * * *' . 'Sync'))
        ->and($second->mutexName())->toBe($first->mutexName())
        ->and($different->mutexName())->not->toBe($first->mutexName());
});

it('throws a helpful SchedulerException when withoutOverlapping is used without a description', function (): void {
    $task = new ScheduledTask(fn (): null => null)->everyMinute()->withoutOverlapping();

    try {
        $task->mutexName();
        $this->fail('Expected SchedulerException');
    } catch (SchedulerException $e) {
        expect($e->getMessage())->toContain('withoutOverlapping()')
            ->and($e->getMessage())->toContain('* * * * *')
            ->and($e->getSuggestion())->toContain('description(');
    }
});
