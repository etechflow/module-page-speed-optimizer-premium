<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Console\Command;

use ETechFlow\PageSpeedOptimizerPremium\Model\BulkSweeper;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `bin/magento etechflow:pso-premium:sweep-folders [--limit=500]`
 *
 * Scans configured media folders and enqueues un-optimized images into
 * the PSO view queue. Pro's existing cron worker then drains the queue.
 *
 * Same cron job runs automatically (etc/crontab.xml — daily at 03:30)
 * but the CLI is here for manual sweeps + CI pipelines.
 */
class SweepFoldersCommand extends Command
{
    private const OPT_LIMIT = 'limit';

    public function __construct(
        private readonly AppState $appState,
        private readonly BulkSweeper $sweeper
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('etechflow:pso-premium:sweep-folders')
            ->setDescription('Walk media folders + enqueue un-optimized images for PSO Pro\'s cron worker to process.')
            ->addOption(self::OPT_LIMIT, null, InputOption::VALUE_REQUIRED,
                'Max entries to enqueue this run (default: admin batch_size, fallback 500).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
        } catch (\Exception $e) {
            // already set
        }

        $limit = $input->getOption(self::OPT_LIMIT) !== null
            ? (int) $input->getOption(self::OPT_LIMIT)
            : null;

        $output->writeln('<info>Sweeping media folders for un-optimized images...</info>');
        $counts = $this->sweeper->sweep($limit);
        $output->writeln('');
        $output->writeln(sprintf('  Scanned:           <info>%d</info>', $counts['scanned']));
        $output->writeln(sprintf('  Already optimized: %d (skipped)', $counts['already_optimized']));
        $output->writeln(sprintf('  Enqueued:          <info>%d</info>', $counts['enqueued']));
        $output->writeln('');
        $output->writeln('Pro\'s cron will drain the queue automatically.');
        $output->writeln('To drain immediately: <comment>bin/magento etechflow:pso:process-view-queue --limit=' . $counts['enqueued'] . '</comment>');
        return Command::SUCCESS;
    }
}
