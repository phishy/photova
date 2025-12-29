<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Browsershot\Browsershot;

class GenerateOgImage extends Command
{
    protected $signature = 'og:generate {--output= : Output path (default: public/og-image.png)}';
    protected $description = 'Generate the OpenGraph image for Photova';

    public function handle(): int
    {
        $outputPath = $this->option('output') ?? public_path('og-image.png');

        $this->info('Generating OG image with Browsershot...');

        $html = view('og.image')->render();

        Browsershot::html($html)
            ->setNodeBinary(exec('which node'))
            ->setNpmBinary(exec('which npm'))
            ->windowSize(1200, 630)
            ->deviceScaleFactor(2)
            ->waitUntilNetworkIdle()
            ->save($outputPath);

        $this->info("OG image saved to: {$outputPath}");

        return Command::SUCCESS;
    }
}
