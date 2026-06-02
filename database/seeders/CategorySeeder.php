<?php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Web Development', 'description' => 'Full-stack web applications', 'color' => '#3B82F6'],
            ['name' => 'Mobile Apps', 'description' => 'iOS and Android applications', 'color' => '#10B981'],
            ['name' => 'API Development', 'description' => 'RESTful and GraphQL APIs', 'color' => '#F59E0B'],
            ['name' => 'Technology', 'description' => 'Tech articles and tutorials', 'color' => '#EF4444'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
