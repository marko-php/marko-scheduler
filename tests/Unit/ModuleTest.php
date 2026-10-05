<?php

declare(strict_types=1);

use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Container\BindingRegistry;
use Marko\Core\Container\Container;
use Marko\Core\Module\ManifestParser;
use Marko\Core\Module\ModuleManifest;
use Marko\Core\Path\ProjectPaths;
use Marko\Scheduler\Command\RunScheduleCommand;
use Marko\Scheduler\Command\ScheduleWorkCommand;
use Marko\Scheduler\Schedule;

/**
 * Build a container wired exactly as the Application does: the real scheduler
 * manifest registered through BindingRegistry, then module boot callbacks called.
 */
function bootSchedulerContainer(
    string $basePath,
    ?Closure $appBoot = null,
): Container {
    $container = new Container();
    $container->instance(ProjectPaths::class, new ProjectPaths($basePath));

    $registry = new BindingRegistry($container);
    $schedulerManifest = new ManifestParser()->parse(dirname(__DIR__, 2));
    $registry->registerModule($schedulerManifest);

    $appManifest = new ModuleManifest(
        name: 'app/tasks',
        version: '1.0.0',
        source: 'app',
        boot: $appBoot,
    );
    $registry->registerModule($appManifest);

    foreach ([$schedulerManifest, $appManifest] as $manifest) {
        if ($manifest->boot !== null) {
            $container->call($manifest->boot);
        }
    }

    return $container;
}

beforeEach(function (): void {
    $this->basePath = sys_get_temp_dir() . '/marko-scheduler-module-' . bin2hex(random_bytes(6));
    mkdir($this->basePath, 0755, true);
});

afterEach(function (): void {
    if (is_dir($this->basePath)) {
        exec('rm -rf ' . escapeshellarg($this->basePath));
    }
});

it('declares Schedule as a singleton in module.php', function (): void {
    $config = require dirname(__DIR__, 2) . '/module.php';

    expect($config)->toHaveKey('singletons')
        ->and($config['singletons'])->toContain(Schedule::class);
});

it('resolves the same Schedule instance from the container on every request', function (): void {
    $container = bootSchedulerContainer($this->basePath);

    expect($container->get(Schedule::class))->toBe($container->get(Schedule::class));
});

it('executes a task registered through a module boot callback when schedule:run runs', function (): void {
    $executed = false;
    $container = bootSchedulerContainer(
        $this->basePath,
        function (Schedule $schedule) use (&$executed): void {
            $schedule->call(function () use (&$executed): void {
                $executed = true;
            })->everyMinute()->description('Boot-registered task');
        },
    );

    $stream = fopen('php://memory', 'r+');
    $exitCode = $container->get(RunScheduleCommand::class)
        ->execute(new Input(['marko', 'schedule:run']), new Output($stream));
    rewind($stream);
    $output = stream_get_contents($stream);

    expect($executed)->toBeTrue()
        ->and($output)->toContain('Executed: Boot-registered task')
        ->and($exitCode)->toBe(0);
});

it('resolves schedule:work from the container', function (): void {
    $container = bootSchedulerContainer($this->basePath);

    expect($container->get(ScheduleWorkCommand::class))->toBeInstanceOf(ScheduleWorkCommand::class);
});
