<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\ServiceSubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing categories and sub-categories to prevent duplicate slug issues
        ServiceSubCategory::query()->delete();
        ServiceCategory::query()->delete();

        $categories = [
            [
                'name' => 'Home Services',
                'slug' => 'home-services',
                'description' => 'Home maintenance and repair',
                'sort_order' => 1,
                'is_active' => true,
                'subcategories' => [
                    'Plumbing',
                    'Electrical',
                    'Cleaning',
                    'Painting',
                    'Pest Control',
                    'Moving'
                ]
            ],
            [
                'name' => 'Repair Services',
                'slug' => 'repair-services',
                'description' => 'All types of repair work',
                'sort_order' => 2,
                'is_active' => true,
                'subcategories' => [
                    'AC Repair',
                    'Appliance Repair',
                    'Furniture Repair'
                ]
            ],
            [
                'name' => 'Automotive',
                'slug' => 'automotive',
                'description' => 'Car and vehicle services',
                'sort_order' => 3,
                'is_active' => true,
                'subcategories' => [
                    'Car Washing',
                    'Car Repair',
                    'Tyre Change'
                ]
            ],
            [
                'name' => 'Other Services',
                'slug' => 'other-services',
                'description' => 'Miscellaneous services',
                'sort_order' => 4,
                'is_active' => true,
                'subcategories' => [
                    'Gardening',
                    'Security',
                    'Photography'
                ]
            ],
        ];

        foreach ($categories as $catData) {
            $subcategories = $catData['subcategories'];
            unset($catData['subcategories']);

            $category = ServiceCategory::create(array_merge($catData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            foreach ($subcategories as $index => $subName) {
                ServiceSubCategory::create([
                    'service_category_id' => $category->id,
                    'name' => $subName,
                    'slug' => Str::slug($subName),
                    'description' => $subName . ' services',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
