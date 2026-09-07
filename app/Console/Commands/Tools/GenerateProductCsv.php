<?php

namespace App\Console\Commands\Tools;

use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateProductCsv extends Command
{
    protected $signature = 'generate:products 
                            {count=20 : Number of products}
                            {--path=storage/app/products.csv : File path}';

    protected $description = 'Generate dummy product CSV for bulk import';

    public function handle()
    {
        $count = (int) $this->argument('count');
        $path = $this->option('path');

        $faker = Faker::create();

        $headers = [
            'name',
            'slug',
            'category_slug',
            'unit',
            'product_type',
            'description',
        ];

        $categories = ['indoor-plants', 'succulents', 'outdoor-plants'];
        $units = ['pcs', 'kg'];
        $types = ['sellable', 'catalog'];

        $file = fopen($path, 'w');

        // Add header
        fputcsv($file, $headers);

        for ($i = 0; $i < $count; $i++) {

            $name = ucfirst($faker->words(3, true));

            $row = [
                $name,
                Str::slug($name),
                $faker->randomElement($categories),
                $faker->randomElement($units),
                $faker->randomElement($types),
                $faker->sentence(10),
            ];

            fputcsv($file, $row);
        }

        fclose($file);

        $this->info("✅ CSV generated at: {$path}");
    }
}
