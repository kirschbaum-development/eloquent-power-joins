<?php

namespace Kirschbaum\PowerJoins;

use Hyperf\Event\Contract\ListenerInterface;
use Kirschbaum\PowerJoins\EloquentJoins;
use Psr\Container\ContainerInterface;
use Hyperf\Database\Model\Booted;

class PowerJoinListener implements ListenerInterface
{
    public function __construct(protected ContainerInterface $container) {}

    public function listen(): array
    {
        return [
            Booted::class,
        ];
    }

    public function process(object $event): void
    {
        EloquentJoins::registerEloquentMacros();
    }
}
