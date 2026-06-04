<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentTemplate;

class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'file_name' => '1009-Consent for Homecare Services & Client Agreement_TX.pdf',
                'name' => 'Consent for Homecare Services',
                'description' => '2-page consent form for homecare service and client agreement.',
            ],
            [
                'file_name' => '1081-Consent to Release-Obtain Information_TX.pdf',
                'name' => 'Consent to Release/Obtain Information',
                'description' => 'Authorization to release or obtain client information.',
            ],
            [
                'file_name' => '1083-Assignment of Benefits_TX.pdf',
                'name' => 'Assignment of Benefits',
                'description' => 'Insurance assignment of benefits form.',
            ],
            [
                'file_name' => '1084-Automatic Payment_TX.pdf',
                'name' => 'Automatic Payment Authorization',
                'description' => 'Recurring/one-time payment authorization for bank or card.',
            ],
            [
                'file_name' => '110-Physical Assessment-Info_TX.pdf',
                'name' => 'Physical Assessment',
                'description' => '3-page physical assessment form with vitals, meds, and body systems.',
            ],
            [
                'file_name' => '1220-Vehicle Release-Waiver_TX.pdf',
                'name' => 'Vehicle Release & Waiver',
                'description' => 'Vehicle use release and liability waiver.',
            ],
            [
                'file_name' => '130-Infant-Child Assessment_TX.pdf',
                'name' => 'Infant/Child Assessment',
                'description' => 'Pediatric assessment form with body systems and vitals.',
            ],
            [
                'file_name' => '324-Personal Assistants May Not Do_TX.pdf',
                'name' => 'Personal Assistants May Not Do',
                'description' => 'Acknowledgement of tasks personal assistants cannot perform.',
            ],
            [
                'file_name' => '325-Participant Agreement Release_TX.pdf',
                'name' => 'Participant Agreement & Release',
                'description' => 'Participant agreement and release of liability.',
            ],
            [
                'file_name' => '350-Client Handbook Acknowledgement_TX.pdf',
                'name' => 'Client Handbook Acknowledgement',
                'description' => 'Acknowledgement of receipt of client handbook.',
            ],
            [
                'file_name' => '400-Care Instructions_TX.pdf',
                'name' => 'Care Instructions',
                'description' => 'Detailed care instructions covering assessment, activities, elimination, personal care, medications, housekeeping, mobility, nutrition, specialty, safety, and records.',
            ],
            [
                'file_name' => '410-Care Plan Acknoweledgement_TX.pdf',
                'name' => 'Care Plan Acknowledgement',
                'description' => 'Acknowledgement and acceptance of the care plan.',
            ],
            [
                'file_name' => '7000-Emergency Plan_TX.pdf',
                'name' => 'Emergency Plan',
                'description' => 'Emergency plan with contacts, physicians, hospital, and priority level.',
            ],
            [
                'file_name' => '7050-Home Environment Safety Checklist_TX.pdf',
                'name' => 'Home Environment Safety Checklist',
                'description' => 'Comprehensive home safety checklist covering stairs, carpet, furniture, bathroom, bedroom, kitchen, living room, basement, pets, fire safety, equipment, pests, and structural.',
            ],
            [
                'file_name' => '790-Case Notes-TX.pdf',
                'name' => 'Case Notes',
                'description' => 'Free-form case notes documentation.',
            ],
        ];

        foreach ($templates as $template) {
            DocumentTemplate::firstOrCreate(
                ['file_name' => $template['file_name']],
                [
                    'name' => $template['name'],
                    'description' => $template['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
