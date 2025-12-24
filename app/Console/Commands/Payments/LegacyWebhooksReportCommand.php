<?php

namespace App\Console\Commands\Payments;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Generate a report of legacy webhook endpoint usage from logs.
 *
 * How to test:
 * 1. Force a legacy webhook hit (if LEGACY_WEBHOOKS_ENABLED=true):
 *    curl.exe -X POST http://127.0.0.1:8000/webhooks/stripe -H "Content-Type: application/json" -d "{}"
 *
 * 2. Run the report with debug mode:
 *    php artisan payments:legacy-webhooks-report --hours=1 --debug=1 --auto-file=1
 *
 * 3. Verify:
 *    - Entries scanned count is correct
 *    - Entries matched count shows used/blocked events
 *    - First matched entry example shows the full log entry
 *    - Extracted context shows the JSON properly parsed
 *    - Table shows provider, route/path, used/blocked counts, IPs
 */
class LegacyWebhooksReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:legacy-webhooks-report 
                            {--hours=24 : Number of hours to analyze}
                            {--file=storage/logs/laravel.log : Log file path}
                            {--auto-file=0 : Auto-fallback to daily log file if default file not found}
                            {--debug=0 : Enable debug mode to show parsing statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a report of legacy webhook endpoint usage from logs';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $filePath = $this->option('file');
        $autoFile = filter_var($this->option('auto-file'), FILTER_VALIDATE_BOOLEAN);
        $debug = filter_var($this->option('debug'), FILTER_VALIDATE_BOOLEAN);

        // Patch 3: Auto-fallback to daily log if default file not found
        $absolutePath = base_path($filePath);
        $defaultFilePath = base_path('storage/logs/laravel.log');

        if (!file_exists($absolutePath) && $autoFile && $filePath === 'storage/logs/laravel.log') {
            $today = Carbon::now()->format('Y-m-d');
            $dailyLogPath = base_path("storage/logs/laravel-{$today}.log");
            
            if (file_exists($dailyLogPath)) {
                $absolutePath = $dailyLogPath;
                $this->warn("Default log file not found, using daily log: {$dailyLogPath}");
            }
        }

        // Vérifier que le fichier existe
        if (!file_exists($absolutePath)) {
            $this->error("Log file not found: {$absolutePath}");
            return 1;
        }

        if (!is_readable($absolutePath)) {
            $this->error("Log file is not readable: {$absolutePath}");
            return 1;
        }

        $this->info("Analyzing logs from the last {$hours} hours...");
        $this->info("Reading file: {$absolutePath}");

        $since = Carbon::now()->subHours($hours);
        $stats = $this->parseLogFile($absolutePath, $since, $debug, $absolutePath);

        if (empty($stats)) {
            $this->warn("No legacy webhook events found in the specified time range.");
            return 0;
        }

        $this->displayReport($stats);

        return 0;
    }

    /**
     * Parse the log file and extract legacy webhook events
     *
     * @param string $filePath
     * @param Carbon $since
     * @param bool $debug
     * @param string|null $actualFilePath For debug display
     * @return array
     */
    private function parseLogFile(string $filePath, Carbon $since, bool $debug = false, ?string $actualFilePath = null): array
    {
        $stats = [];
        $handle = fopen($filePath, 'r');

        if (!$handle) {
            $this->error("Failed to open log file: {$filePath}");
            return [];
        }

        $buffer = null;
        $entriesScanned = 0;
        $entriesMatched = 0;
        $firstContextExample = null;

        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line)) {
                if ($buffer !== null) {
                    $entriesScanned++;
                    $matched = $this->processLogEntry(trim($buffer), $since, $stats, $debug, $firstContextExample === null);
                    if ($matched && $firstContextExample === null) {
                        $firstContextExample = trim($buffer);
                    }
                    if ($matched) {
                        $entriesMatched++;
                    }
                }
                $buffer = $line;
                continue;
            }

            if ($buffer !== null) {
                $buffer .= $line;
            }
        }

        if ($buffer !== null) {
            $entriesScanned++;
            $matched = $this->processLogEntry(trim($buffer), $since, $stats, $debug, $firstContextExample === null);
            if ($matched && $firstContextExample === null) {
                $firstContextExample = trim($buffer);
            }
            if ($matched) {
                $entriesMatched++;
            }
        }

        fclose($handle);

        // Patch 4: Debug output
        if ($debug) {
            $this->newLine();
            $this->info('=== Debug Statistics ===');
            $this->line("  File actually used: " . ($actualFilePath ?? $filePath));
            $this->line("  Total log entries (buffers) processed: {$entriesScanned}");
            $this->line("  Entries matched (used/blocked): {$entriesMatched}");
            
            if ($firstContextExample !== null) {
                $this->newLine();
                $this->info('First matched entry example:');
                $this->line(str_repeat('-', 80));
                $this->line($firstContextExample);
                $this->line(str_repeat('-', 80));
                
                $context = $this->extractContext($firstContextExample);
                if (!empty($context)) {
                    $this->newLine();
                    $this->info('Extracted context:');
                    $this->line(json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
            $this->newLine();
        }

        return $stats;
    }

    /**
     * Process a single log entry
     *
     * @param string $entry
     * @param Carbon $since
     * @param array &$stats
     * @param bool $debug
     * @param bool $isFirstMatch
     * @return bool Returns true if entry matched (used/blocked), false otherwise
     */
    private function processLogEntry(string $entry, Carbon $since, array &$stats, bool $debug = false, bool $isFirstMatch = false): bool
    {
        // Extract timestamp
        if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $entry, $matches)) {
            return false;
        }

        try {
            $timestamp = Carbon::parse($matches[1]);
        } catch (\Exception $e) {
            return false;
        }

        // Skip if entry is too old
        if ($timestamp->lt($since)) {
            return false;
        }

        // Check for legacy webhook messages
        $isUsed = Str::contains($entry, 'Legacy webhook used');
        $isBlocked = Str::contains($entry, 'Legacy webhook blocked');

        if (!$isUsed && !$isBlocked) {
            return false;
        }

        // Extract JSON context
        $context = $this->extractContext($entry);

        if (empty($context)) {
            return false;
        }

        $provider = $context['provider'] ?? 'unknown';
        $routeName = $context['route_name'] ?? null;
        $path = $context['path'] ?? 'unknown';
        $ip = $context['ip'] ?? 'unknown';

        // Use route_name if available, otherwise use path
        $key = $provider . ':' . ($routeName ?? $path);

        if (!isset($stats[$key])) {
            $stats[$key] = [
                'provider' => $provider,
                'route_name' => $routeName,
                'path' => $path,
                'used_count' => 0,
                'blocked_count' => 0,
                'last_seen' => $timestamp,
                'ips' => [],
            ];
        }

        if ($isUsed) {
            $stats[$key]['used_count']++;
        }

        if ($isBlocked) {
            $stats[$key]['blocked_count']++;
        }

        // Update last_seen if this entry is more recent
        if ($timestamp->gt($stats[$key]['last_seen'])) {
            $stats[$key]['last_seen'] = $timestamp;
        }

        // Track IPs
        if ($ip !== 'unknown') {
            if (!isset($stats[$key]['ips'][$ip])) {
                $stats[$key]['ips'][$ip] = 0;
            }
            $stats[$key]['ips'][$ip]++;
        }

        return true;
    }

    /**
     * Extract JSON context from log entry
     *
     * @param string $entry
     * @return array
     */
    private function extractContext(string $entry): array
    {
        $pos = strrpos($entry, '{');
        if ($pos === false) {
            return [];
        }

        $jsonPart = substr($entry, $pos);
        $json = json_decode($jsonPart, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            return $json;
        }

        return [];
    }

    /**
     * Display the report in a formatted table
     *
     * @param array $stats
     * @return void
     */
    private function displayReport(array $stats): void
    {
        $this->newLine();
        $this->info('Legacy Webhooks Report');
        $this->info(str_repeat('=', 80));

        $tableData = [];

        foreach ($stats as $key => $stat) {
            // Get top 3 IPs
            arsort($stat['ips']);
            $topIps = array_slice(array_keys($stat['ips']), 0, 3);
            $topIpsStr = empty($topIps) ? 'N/A' : implode(', ', $topIps);

            $tableData[] = [
                'Provider' => $stat['provider'],
                'Route/Path' => $stat['route_name'] ?? $stat['path'],
                'Used' => $stat['used_count'],
                'Blocked' => $stat['blocked_count'],
                'Last Seen' => $stat['last_seen']->format('Y-m-d H:i:s'),
                'Top IPs' => $topIpsStr,
            ];
        }

        // Sort by total hits (used + blocked) descending
        usort($tableData, function ($a, $b) {
            $totalA = $a['Used'] + $a['Blocked'];
            $totalB = $b['Used'] + $b['Blocked'];
            return $totalB <=> $totalA;
        });

        $this->table(
            ['Provider', 'Route/Path', 'Used', 'Blocked', 'Last Seen', 'Top IPs'],
            $tableData
        );

        // Summary
        $totalUsed = array_sum(array_column($stats, 'used_count'));
        $totalBlocked = array_sum(array_column($stats, 'blocked_count'));

        $this->newLine();
        $this->info('Summary:');
        $this->line("  Total Used:   {$totalUsed}");
        $this->line("  Total Blocked: {$totalBlocked}");
        $this->line("  Total Events:  " . ($totalUsed + $totalBlocked));
    }
}

