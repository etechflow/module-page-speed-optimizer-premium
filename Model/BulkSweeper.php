<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Model;

use ETechFlow\PageSpeedOptimizer\Model\ViewQueue\ViewTracker;
use ETechFlow\PageSpeedOptimizer\Model\ResourceModel\OptimizationLog as OptimizationLogResource;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Bulk-sweep walker: scans configured media folders and enqueues every
 * un-optimized image into PSO Pro's view_queue.
 *
 * Different from Pro's auto-on-upload observer:
 *   - auto-on-upload: catches NEW product image uploads only
 *   - bulk-sweep: catches ALL existing folder content
 *
 * Useful for stores migrating from another optimizer or after enabling
 * PSO retroactively. Walks once per cron run; idempotent because the
 * view_queue table has UNIQUE on source_path (INSERT IGNORE).
 *
 * Detection of "already optimized": checks the optimization_log for a
 * row with matching source_path + status=ok. Skips those.
 */
class BulkSweeper
{
    private const SUPPORTED_EXT = ['jpg', 'jpeg', 'png', 'gif'];

    public function __construct(
        private readonly Config $config,
        private readonly ViewTracker $viewTracker,
        private readonly OptimizationLogResource $logResource,
        private readonly Filesystem $filesystem,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Sweep one batch — returns counts.
     *
     * @return array{scanned: int, enqueued: int, already_optimized: int}
     */
    public function sweep(?int $batchSize = null): array
    {
        $counts = ['scanned' => 0, 'enqueued' => 0, 'already_optimized' => 0];
        if (!$this->config->isBulkSweepEnabled()) {
            return $counts;
        }

        $limit = $batchSize ?? $this->config->getBulkSweepBatchSize();
        try {
            $mediaDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        } catch (\Throwable $e) {
            $this->logger->warning('ETechFlow_PSO_Premium bulk-sweep: media dir unreadable', ['exception' => $e->getMessage()]);
            return $counts;
        }
        $alreadyOptimized = $this->loadAlreadyOptimizedSet();

        foreach ($this->config->getBulkSweepScanPaths() as $relativePath) {
            $absolutePath = rtrim($mediaDir, '/') . '/' . ltrim($relativePath, '/');
            if (!is_dir($absolutePath)) {
                continue;
            }
            foreach ($this->walkImages($absolutePath) as $file) {
                $counts['scanned']++;
                if (isset($alreadyOptimized[$file])) {
                    $counts['already_optimized']++;
                    continue;
                }
                $this->viewTracker->enqueue($file);
                $counts['enqueued']++;
                if ($counts['enqueued'] >= $limit) {
                    return $counts;
                }
            }
        }
        return $counts;
    }

    /**
     * @return array<string, true>
     */
    private function loadAlreadyOptimizedSet(): array
    {
        $connection = $this->logResource->getConnection();
        $select = $connection->select()
            ->from($this->logResource->getMainTable(), ['source_path'])
            ->where('status = ?', 'ok')
            ->distinct(true);
        $rows = $connection->fetchCol($select);
        $set = [];
        foreach ($rows as $path) {
            $set[$path] = true;
        }
        return $set;
    }

    /**
     * @return \Generator<string>
     */
    private function walkImages(string $root): \Generator
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $ext = strtolower($file->getExtension());
            if (in_array($ext, self::SUPPORTED_EXT, true)) {
                yield $file->getPathname();
            }
        }
    }
}
