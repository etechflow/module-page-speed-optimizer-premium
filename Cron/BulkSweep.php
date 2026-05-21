<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Cron;

use ETechFlow\PageSpeedOptimizerPremium\Model\BulkSweeper;
use Psr\Log\LoggerInterface;

/**
 * Cron entry — runs once per day at 03:30 (configurable in crontab.xml).
 * Walks media folders and enqueues un-optimized images.
 */
class BulkSweep
{
    public function __construct(
        private readonly BulkSweeper $sweeper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        try {
            $counts = $this->sweeper->sweep();
            $this->logger->info('ETechFlow_PSO_Premium bulk-sweep cron complete', $counts);
        } catch (\Throwable $e) {
            $this->logger->error(
                'ETechFlow_PSO_Premium bulk-sweep cron failed',
                ['exception' => $e->getMessage()]
            );
        }
    }
}
