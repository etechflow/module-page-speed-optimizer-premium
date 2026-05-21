<?php

declare(strict_types=1);

namespace ETechFlow\PageSpeedOptimizerPremium\Console\Command;

use ETechFlow\PageSpeedOptimizerPremium\Model\BulkSweeper;
use ETechFlow\PageSpeedOptimizerPremium\Model\Config;
use ETechFlow\PageSpeedOptimizerPremium\Model\LicenseValidator;
use ETechFlow\PageSpeedOptimizer\Model\Config as PsoProConfig;
use ETechFlow\PageSpeedOptimizer\Model\ViewQueue\ViewTracker;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Module\Manager as ModuleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `bin/magento etechflow:pso-premium:verify` — 7-check smoke test.
 */
class VerifyCommand extends Command
{
    private int $checksRun = 0;
    private int $checksFailed = 0;

    public function __construct(
        private readonly AppState $appState,
        private readonly LicenseValidator $licenseValidator,
        private readonly Config $config,
        private readonly ModuleManager $moduleManager,
        private readonly PsoProConfig $proConfig,
        private readonly BulkSweeper $sweeper,
        private readonly ViewTracker $viewTracker
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('etechflow:pso-premium:verify')
            ->setDescription('Smoke-test ETechFlow Page Speed Optimizer Premium.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->appState->setAreaCode(Area::AREA_GLOBAL);
        } catch (\Exception $e) {
            // already set
        }

        $output->writeln('=== ETechFlow Page Speed Optimizer Premium verify ===');
        $output->writeln('');

        $this->check($output, 'Pro module (ETechFlow_PageSpeedOptimizer) is enabled', function () {
            if (!$this->moduleManager->isEnabled('ETechFlow_PageSpeedOptimizer')) {
                throw new \RuntimeException('Pro module is not enabled — Premium requires it as a dependency');
            }
            return 'OK';
        });

        $this->check($output, 'Premium LicenseValidator evaluates', function () {
            $host = $this->licenseValidator->getCurrentHost();
            return sprintf('host=%s; valid=%s', $host ?: '(none)',
                $this->licenseValidator->isValid() ? 'yes' : 'no');
        });

        $this->check($output, 'Premium Config.isEnabled() works', function () {
            return 'enabled=' . ($this->config->isEnabled() ? 'yes' : 'no');
        });

        $this->check($output, 'Pro Config also reachable (for view-queue handoff)', function () {
            return 'pro_enabled=' . ($this->proConfig->isEnabled() ? 'yes' : 'no');
        });

        $this->check($output, 'Infinite Scroll settings reachable', function () {
            return sprintf('enabled=%s; on_category=%s; on_search=%s; threshold=%dpx; max_pages=%d',
                $this->config->isInfiniteScrollEnabled() ? 'yes' : 'no',
                $this->config->isInfiniteScrollOnCategory() ? 'yes' : 'no',
                $this->config->isInfiniteScrollOnSearch() ? 'yes' : 'no',
                $this->config->getScrollThresholdPx(),
                $this->config->getMaxAutoLoadPages());
        });

        $this->check($output, 'BulkSweeper resolves via DI', function () {
            return get_class($this->sweeper);
        });

        $this->check($output, 'PSO Pro ViewTracker resolves (handoff target for sweeper)', function () {
            return get_class($this->viewTracker);
        });

        $output->writeln('');
        if ($this->checksFailed === 0) {
            $output->writeln(sprintf('<info>All %d checks passed.</info>', $this->checksRun));
            return Command::SUCCESS;
        }
        $output->writeln(sprintf('<error>%d of %d checks FAILED.</error>', $this->checksFailed, $this->checksRun));
        return Command::FAILURE;
    }

    private function check(OutputInterface $output, string $name, callable $fn): void
    {
        $this->checksRun++;
        $output->write(sprintf('%2d. %s ... ', $this->checksRun, $name));
        try {
            $detail = $fn();
            $output->writeln(sprintf('<info>OK</info> (%s)', $detail));
        } catch (\Throwable $e) {
            $this->checksFailed++;
            $output->writeln(sprintf('<error>FAIL: %s</error>', $e->getMessage()));
        }
    }
}
