<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class GenerateResponsiveImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:responsive
                            {--dry-run : Preview what would be generated without creating files}
                            {--force : Regenerate existing responsive variants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate responsive image variants (400w, 800w, 1200w) for srcset optimization';

    /**
     * Responsive widths to generate
     */
    protected array $widths = [400, 800, 1200];

    /**
     * Quality settings per width
     */
    protected array $quality = [
        400 => 80,
        800 => 85,
        1200 => 90,
    ];

    /**
     * Supported image extensions
     */
    protected array $extensions = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Statistics
     */
    protected int $processedCount = 0;
    protected int $skippedCount = 0;
    protected int $generatedCount = 0;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📸 Responsive Image Generator');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No files will be created');
            $this->newLine();
        }

        // Scan storage/app/public recursively
        $publicPath = storage_path('app/public');

        if (!File::exists($publicPath)) {
            $this->error("Storage directory not found: {$publicPath}");
            return self::FAILURE;
        }

        $this->info("Scanning: {$publicPath}");
        $this->newLine();

        // Find all images
        $images = $this->findImages($publicPath);

        if ($images->isEmpty()) {
            $this->warn('No images found.');
            return self::SUCCESS;
        }

        $this->info("Found {$images->count()} image(s)");
        $this->newLine();

        // Progress bar
        $bar = $this->output->createProgressBar($images->count());
        $bar->start();

        foreach ($images as $imagePath) {
            $this->processImage($imagePath, $publicPath);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✅ Responsive image generation complete!');
        $this->newLine();
        $this->line("  Processed: {$this->processedCount}");
        $this->line("  Generated: {$this->generatedCount} variants");
        $this->line("  Skipped:   {$this->skippedCount}");
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('Run without --dry-run to generate files.');
        }

        return self::SUCCESS;
    }

    /**
     * Find all images in directory recursively
     */
    protected function findImages(string $path): \Illuminate\Support\Collection
    {
        $images = collect();

        $files = File::allFiles($path);

        foreach ($files as $file) {
            $extension = strtolower($file->getExtension());

            if (in_array($extension, $this->extensions)) {
                // Skip already generated responsive variants
                if (!$this->isResponsiveVariant($file->getFilename())) {
                    $images->push($file->getRealPath());
                }
            }
        }

        return $images;
    }

    /**
     * Check if filename is a responsive variant
     */
    protected function isResponsiveVariant(string $filename): bool
    {
        foreach ($this->widths as $width) {
            if (str_contains($filename, "_${width}w.")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Process a single image and generate responsive variants
     */
    protected function processImage(string $imagePath, string $basePath): void
    {
        $this->processedCount++;

        $pathInfo = pathinfo($imagePath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];

        foreach ($this->widths as $width) {
            $variantName = "{$filename}_{$width}w.{$extension}";
            $variantPath = "{$directory}/{$variantName}";

            // Skip if exists and not forcing
            if (File::exists($variantPath) && !$this->option('force')) {
                $this->skippedCount++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->generatedCount++;
                continue;
            }

            try {
                // Generate responsive variant
                $image = Image::read($imagePath);

                // Resize maintaining aspect ratio
                $image->scale(width: $width);

                // Encode with appropriate quality
                $quality = $this->quality[$width];

                // Save
                $image->save($variantPath, quality: $quality);

                $this->generatedCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to process {$imagePath}: {$e->getMessage()}");
                $this->skippedCount++;
            }
        }
    }
}
