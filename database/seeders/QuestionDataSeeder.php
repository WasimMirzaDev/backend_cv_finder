<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuestionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cv_subcategories')->truncate();
        DB::table('cv_questiontypes')->truncate();
        DB::table('cv_difficultylevels')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Insert Difficulty Levels
        $difficulties = [
            ['name' => 'Easy', 'slug' => 'E'],
            ['name' => 'Medium', 'slug' => 'M'],
            ['name' => 'Hard', 'slug' => 'H']
        ];
        DB::table('cv_difficultylevels')->insert($difficulties);

        // 2. Insert Question Types
        $questionTypes = [
            ['name' => 'Industry', 'slug' => 'IND'],
            ['name' => 'Business Sector', 'slug' => 'BUS'],
            ['name' => 'Behavioural', 'slug' => 'BEH'],
            ['name' => 'Get to Know', 'slug' => 'GTK']
        ];
        DB::table('cv_questiontypes')->insert($questionTypes);

        // 3. Get question type IDs
        $industryId = DB::table('cv_questiontypes')->where('slug', 'IND')->first()->id;
        $businessId = DB::table('cv_questiontypes')->where('slug', 'BUS')->first()->id;
        $behaviouralId = DB::table('cv_questiontypes')->where('slug', 'BEH')->first()->id;
        $gtkId = DB::table('cv_questiontypes')->where('slug', 'GTK')->first()->id;

        // 4. Insert Subcategories for each Question Type
        $subcategories = [
            // Industry subcategories
            ['questiontype_id' => $industryId, 'name' => 'Tech', 'slug' => 'T'],
            ['questiontype_id' => $industryId, 'name' => 'Finance', 'slug' => 'F'],
            ['questiontype_id' => $industryId, 'name' => 'Professional Services', 'slug' => 'P'],
            ['questiontype_id' => $industryId, 'name' => 'Marketing', 'slug' => 'M'],
            ['questiontype_id' => $industryId, 'name' => 'Engineering', 'slug' => 'E'],
            ['questiontype_id' => $industryId, 'name' => 'Healthcare', 'slug' => 'H'],
            ['questiontype_id' => $industryId, 'name' => 'Education', 'slug' => 'Ed'],
            ['questiontype_id' => $industryId, 'name' => 'Legal', 'slug' => 'L'],
            ['questiontype_id' => $industryId, 'name' => 'Public', 'slug' => 'Pu'],
            ['questiontype_id' => $industryId, 'name' => 'Media', 'slug' => 'Me'],
            ['questiontype_id' => $industryId, 'name' => 'Retail', 'slug' => 'R'],
            ['questiontype_id' => $industryId, 'name' => 'Accounting', 'slug' => 'A'],
            ['questiontype_id' => $industryId, 'name' => 'Charity', 'slug' => 'C'],
            ['questiontype_id' => $industryId, 'name' => 'Human Resources', 'slug' => 'Hr'],
            ['questiontype_id' => $industryId, 'name' => 'Supply Chain & Logistics', 'slug' => 'S'],

            // Business Sector subcategories
            ['questiontype_id' => $businessId, 'name' => 'Operations', 'slug' => 'O'],
            ['questiontype_id' => $businessId, 'name' => 'Finance', 'slug' => 'F'],
            ['questiontype_id' => $businessId, 'name' => 'Human Resources', 'slug' => 'Hr'],
            ['questiontype_id' => $businessId, 'name' => 'Marketing', 'slug' => 'M'],
            ['questiontype_id' => $businessId, 'name' => 'Sales', 'slug' => 'S'],
            ['questiontype_id' => $businessId, 'name' => 'Tech', 'slug' => 'T'],
            ['questiontype_id' => $businessId, 'name' => 'Customer Support', 'slug' => 'C'],
            ['questiontype_id' => $businessId, 'name' => 'Legal', 'slug' => 'L'],
            ['questiontype_id' => $businessId, 'name' => 'Supply Chain', 'slug' => 'Sc'],
            ['questiontype_id' => $businessId, 'name' => 'R&D', 'slug' => 'Rd'],

            // Behavioural subcategories
            ['questiontype_id' => $behaviouralId, 'name' => 'Teamwork & Collaboration', 'slug' => 'T'],
            ['questiontype_id' => $behaviouralId, 'name' => 'Communication & Influence', 'slug' => 'C'],
            ['questiontype_id' => $behaviouralId, 'name' => 'Problem Solving & Critical Thinking', 'slug' => 'P'],
            ['questiontype_id' => $behaviouralId, 'name' => 'Resilience & Adaptability', 'slug' => 'R'],
            ['questiontype_id' => $behaviouralId, 'name' => 'Initiative & Ownership', 'slug' => 'I'],

            // Get to Know subcategories
            ['questiontype_id' => $gtkId, 'name' => 'Motivation', 'slug' => 'M'],
            ['questiontype_id' => $gtkId, 'name' => 'Education', 'slug' => 'E'],
            ['questiontype_id' => $gtkId, 'name' => 'Strengths & Personal Development', 'slug' => 'S'],
            ['questiontype_id' => $gtkId, 'name' => 'Interests & Extracurricular', 'slug' => 'I'],
            ['questiontype_id' => $gtkId, 'name' => 'Values & Work Ethic', 'slug' => 'V'],
        ];

        DB::table('cv_subcategories')->insert($subcategories);
    }
}
