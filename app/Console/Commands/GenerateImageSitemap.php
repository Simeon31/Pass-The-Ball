<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateImageSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate-images {--limit=1000 : Maximum number of images to include}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate XML sitemap for public gallery images';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating image sitemap...');

        $limit = (int) $this->option('limit');

        // Get public albums with their photos
        $albums = Album::where('visibility', 'public')
            ->with([
                'photos' => function ($query) use ($limit) {
                    $query->latest()->limit($limit);
                },
                'user'
            ])
            ->whereHas('photos')
            ->get();

        if ($albums->isEmpty()) {
            $this->warn('No public albums with photos found.');
            return Command::SUCCESS;
        }

        // Build XML sitemap
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"></urlset>');

        $photoCount = 0;

        foreach ($albums as $album) {
            $albumUrl = route('gallery.album.show', [
                'username' => $album->user->username,
                'album' => $album->slug,
            ]);

            // Create URL entry for the album
            $url = $xml->addChild('url');
            $url->addChild('loc', htmlspecialchars($albumUrl));
            $url->addChild('lastmod', $album->updated_at->toAtomString());

            // Add images from the album
            foreach ($album->photos as $photo) {
                $imageEntry = $url->addChild('image:image', null, 'http://www.google.com/schemas/sitemap-image/1.1');
                $imageEntry->addChild('image:loc', htmlspecialchars($photo->medium_url), 'http://www.google.com/schemas/sitemap-image/1.1');

                if ($photo->title) {
                    $imageEntry->addChild('image:title', htmlspecialchars($photo->title), 'http://www.google.com/schemas/sitemap-image/1.1');
                }

                if ($photo->description) {
                    $imageEntry->addChild('image:caption', htmlspecialchars($photo->description), 'http://www.google.com/schemas/sitemap-image/1.1');
                }

                $photoCount++;
            }
        }

        // Save the sitemap to public directory
        $xmlContent = $xml->asXML();
        $filePath = public_path('image-sitemap.xml');

        if (file_put_contents($filePath, $xmlContent)) {
            $this->info("✓ Image sitemap generated successfully!");
            $this->info("  Albums: {$albums->count()}");
            $this->info("  Photos: {$photoCount}");
            $this->info("  Location: {$filePath}");
        } else {
            $this->error('Failed to write sitemap file.');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
