<?php
namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            ['name' => 'Laravel', 'category' => 'Backend', 'proficiency' => 90, 'is_featured' => true],
            ['name' => 'PHP', 'category' => 'Backend', 'proficiency' => 85, 'is_featured' => true],
            ['name' => 'JavaScript', 'category' => 'Frontend', 'proficiency' => 80, 'is_featured' => true],
            ['name' => 'Vue.js', 'category' => 'Frontend', 'proficiency' => 75, 'is_featured' => true],
            ['name' => 'MySQL', 'category' => 'Database', 'proficiency' => 80, 'is_featured' => false],
            ['name' => 'Git', 'category' => 'Tools', 'proficiency' => 85, 'is_featured' => false],
        ];

        foreach ($skills as $skill) {
            Skill::create($skill);
        }
    }
}
