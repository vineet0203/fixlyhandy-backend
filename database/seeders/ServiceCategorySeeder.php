<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'home_repair',
                'name' => 'Home Repair Services',
                'description' => 'General home maintenance, repairs, door/window fixes, and mounting services.',
                'icon' => 'Wrench',
                'sort_order' => 1,
            ],
            [
                'slug' => 'electrical',
                'name' => 'Electrical Services',
                'description' => 'Light, fan, wiring, and appliance installations or repairs.',
                'icon' => 'Zap',
                'sort_order' => 2,
            ],
            [
                'slug' => 'plumbing',
                'name' => 'Plumbing Services',
                'description' => 'Fixing leaks, toilets, taps, and water system installations.',
                'icon' => 'Droplets',
                'sort_order' => 3,
            ],
            [
                'slug' => 'painting_wall',
                'name' => 'Painting & Wall Services',
                'description' => 'Interior and exterior painting, waterproofing, and wallpapers.',
                'icon' => 'Paintbrush',
                'sort_order' => 4,
            ],
            [
                'slug' => 'carpentry',
                'name' => 'Carpentry Services',
                'description' => 'Custom shelves, furniture repair, cabinets, and wood repair.',
                'icon' => 'Hammer',
                'sort_order' => 5,
            ],
            [
                'slug' => 'cleaning',
                'name' => 'Cleaning Services',
                'description' => 'Deep home cleaning, sofas, carpets, and sanitization services.',
                'icon' => 'Sparkles',
                'sort_order' => 6,
            ],
            [
                'slug' => 'appliance',
                'name' => 'Appliance Services',
                'description' => 'Repairs and installations for ACs, fridges, washing machines, and microwaves.',
                'icon' => 'Tv',
                'sort_order' => 7,
            ],
            [
                'slug' => 'outdoor',
                'name' => 'Outdoor Services',
                'description' => 'Garden maintenance, lawn mowing, fencing, and pressure washing.',
                'icon' => 'Trees',
                'sort_order' => 8,
            ],
            [
                'slug' => 'smart_home',
                'name' => 'Smart Home & Installation',
                'description' => 'WiFi, security cameras, smart locks, and automation setups.',
                'icon' => 'Cpu',
                'sort_order' => 9,
            ],
            [
                'slug' => 'moving_support',
                'name' => 'Moving & Support Services',
                'description' => 'Packing, shifting assistance, and heavy item moving services.',
                'icon' => 'Truck',
                'sort_order' => 10,
            ],
        ];

        foreach ($categories as $cat) {
            ServiceCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'icon' => $cat['icon'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
