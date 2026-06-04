<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class QuestionEngineService
{
    /**
     * Static question graph: service_type → adjacency list of questions.
     * Each node: id, text, type (text|select|number), options[], weight, skip_if[]
     */
    private array $questionGraph = [
        'cleaning' => [
            ['id' => 'area_size',      'text' => 'What is the approximate area size to be cleaned? (sq ft or sq m)', 'type' => 'text',   'options' => [], 'weight' => 10, 'skip_if' => ['sqft', 'sq ft', 'sq m', 'sqm', 'floor', 'floors']],
            ['id' => 'frequency',      'text' => 'Is this a one-time or recurring clean?',                           'type' => 'select', 'options' => ['One-time', 'Weekly', 'Bi-weekly', 'Monthly'], 'weight' => 9, 'skip_if' => ['weekly', 'monthly', 'daily', 'recurring', 'once']],
            ['id' => 'cleaning_type',  'text' => 'What type of cleaning is needed?',                                 'type' => 'select', 'options' => ['Standard', 'Deep Clean', 'Move-in/Move-out', 'Post-Construction'], 'weight' => 8, 'skip_if' => ['deep', 'standard', 'move']],
            ['id' => 'urgency',        'text' => 'When do you need this done?',                                      'type' => 'select', 'options' => ['ASAP / Urgent', 'Within this week', 'Scheduled date'], 'weight' => 7, 'skip_if' => ['urgent', 'asap', 'emergency', 'scheduled']],
        ],
        'plumbing' => [
            ['id' => 'location_count', 'text' => 'How many locations or fixtures need attention?',                   'type' => 'number', 'options' => [], 'weight' => 10, 'skip_if' => ['fixtures', 'bathrooms', 'locations', 'units']],
            ['id' => 'urgency',        'text' => 'Is this an emergency or a scheduled repair?',                      'type' => 'select', 'options' => ['Emergency', 'Scheduled'], 'weight' => 9, 'skip_if' => ['urgent', 'emergency', 'asap', 'scheduled']],
            ['id' => 'materials',      'text' => 'Do you need standard parts or specific materials?',                'type' => 'select', 'options' => ['Standard Parts', 'Specific Materials Required'], 'weight' => 8, 'skip_if' => ['material', 'parts', 'specific']],
        ],
        'electrical' => [
            ['id' => 'scope',          'text' => 'What is the scope of electrical work?',                            'type' => 'select', 'options' => ['New Installation', 'Repair/Replacement', 'Inspection/Testing', 'Full Rewiring'], 'weight' => 10, 'skip_if' => ['install', 'repair', 'rewir', 'inspect']],
            ['id' => 'panel_upgrade',  'text' => 'Does this involve a panel/breaker box upgrade?',                   'type' => 'select', 'options' => ['Yes', 'No', 'Not Sure'], 'weight' => 9, 'skip_if' => ['panel', 'breaker']],
            ['id' => 'unit_count',     'text' => 'How many outlets, fixtures, or units need work?',                  'type' => 'number', 'options' => [], 'weight' => 8, 'skip_if' => ['outlets', 'fixtures', 'units']],
        ],
        'landscaping' => [
            ['id' => 'area_size',      'text' => 'What is the total area for landscaping? (sq ft or acres)',         'type' => 'text',   'options' => [], 'weight' => 10, 'skip_if' => ['sqft', 'acre', 'sq ft', 'yard']],
            ['id' => 'service_type',   'text' => 'What landscaping services are needed?',                            'type' => 'select', 'options' => ['Lawn Mowing', 'Garden Design', 'Tree Trimming', 'Full Maintenance'], 'weight' => 9, 'skip_if' => ['mow', 'trim', 'design', 'garden', 'tree']],
            ['id' => 'frequency',      'text' => 'Is this recurring or a one-time service?',                         'type' => 'select', 'options' => ['One-time', 'Weekly', 'Bi-weekly', 'Monthly'], 'weight' => 8, 'skip_if' => ['weekly', 'monthly', 'recurring', 'once']],
        ],
        'painting' => [
            ['id' => 'area_size',      'text' => 'What is the area to be painted? (sq ft)',                          'type' => 'text',   'options' => [], 'weight' => 10, 'skip_if' => ['sqft', 'sq ft', 'room', 'floor']],
            ['id' => 'surface',        'text' => 'Interior, exterior, or both?',                                     'type' => 'select', 'options' => ['Interior', 'Exterior', 'Both'], 'weight' => 9, 'skip_if' => ['interior', 'exterior', 'inside', 'outside']],
            ['id' => 'coats',          'text' => 'How many coats of paint are needed?',                              'type' => 'select', 'options' => ['1 coat', '2 coats', '3 coats', 'Not sure'], 'weight' => 8, 'skip_if' => ['coat', 'layer']],
        ],
        'roofing' => [
            ['id' => 'roof_size',      'text' => 'What is the approximate roof size? (sq ft)',                       'type' => 'text',   'options' => [], 'weight' => 10, 'skip_if' => ['sqft', 'sq ft']],
            ['id' => 'job_type',       'text' => 'Is this a repair, replacement, or new installation?',              'type' => 'select', 'options' => ['Repair', 'Full Replacement', 'New Installation', 'Inspection Only'], 'weight' => 9, 'skip_if' => ['repair', 'replace', 'new', 'inspect']],
            ['id' => 'material',       'text' => 'What roofing material do you prefer?',                             'type' => 'select', 'options' => ['Asphalt Shingles', 'Metal', 'Tile', 'Flat/TPO', 'Same as existing'], 'weight' => 8, 'skip_if' => ['asphalt', 'metal', 'tile', 'material']],
        ],
        'hvac' => [
            ['id' => 'units',          'text' => 'How many HVAC units need service?',                                'type' => 'number', 'options' => [], 'weight' => 10, 'skip_if' => ['unit', 'system']],
            ['id' => 'service_type',   'text' => 'What HVAC service is needed?',                                     'type' => 'select', 'options' => ['Installation', 'Repair', 'Maintenance/Tune-up', 'Replacement'], 'weight' => 9, 'skip_if' => ['install', 'repair', 'maintenance', 'replace']],
            ['id' => 'urgency',        'text' => 'Is this urgent or can it be scheduled?',                           'type' => 'select', 'options' => ['Urgent / No AC or Heat', 'Scheduled'], 'weight' => 8, 'skip_if' => ['urgent', 'emergency', 'scheduled']],
        ],
        'ios_development' => [
            ['id' => 'platform',       'text' => 'iOS only, Android only, or cross-platform?',                       'type' => 'select', 'options' => ['iOS Only', 'Android Only', 'Both (React Native/Flutter)'], 'weight' => 10, 'skip_if' => ['ios', 'android', 'cross']],
            ['id' => 'complexity',     'text' => 'What is the app complexity level?',                                 'type' => 'select', 'options' => ['Simple (showcase/brochure)', 'Medium (auth + basic features)', 'Complex (real-time, payments, APIs)'], 'weight' => 9, 'skip_if' => ['simple', 'complex', 'medium', 'feature']],
            ['id' => 'timeline',       'text' => 'What is your target delivery timeline?',                            'type' => 'select', 'options' => ['< 1 month', '1-3 months', '3-6 months', '6+ months'], 'weight' => 8, 'skip_if' => ['month', 'week', 'sprint', 'timeline']],
        ],
        'default' => [
            ['id' => 'scope_detail',   'text' => 'Can you describe the scope of work in more detail?',               'type' => 'text',   'options' => [], 'weight' => 10, 'skip_if' => []],
            ['id' => 'urgency',        'text' => 'When do you need this completed?',                                  'type' => 'select', 'options' => ['ASAP / Urgent', 'Within this week', 'Flexible / Scheduled'], 'weight' => 9, 'skip_if' => ['urgent', 'asap', 'scheduled']],
            ['id' => 'budget',         'text' => 'Do you have a target budget range?',                               'type' => 'select', 'options' => ['Under $500', '$500–$2,000', '$2,000–$10,000', 'No budget set'], 'weight' => 8, 'skip_if' => ['budget', 'cost', 'price', '$']],
        ],
    ];

    /**
     * Select up to $limit clarifying questions for the given service type,
     * skipping anything already evident from scope_hints.
     *
     * @param  string  $serviceType
     * @param  array   $scopeHints
     * @param  string  $urgency
     * @param  int     $limit
     * @return array   [{id, text, type, options}]
     */
    public function getQuestions(string $serviceType, array $scopeHints, string $urgency, int $limit = 3): array
    {
        // Resolve graph nodes for this service type; fallback to 'default'
        $nodes = $this->questionGraph[$serviceType]
            ?? $this->questionGraph[str_replace(['-', ' '], '_', strtolower($serviceType))]
            ?? $this->questionGraph['default'];

        // Flatten scope hints into a single lowercase string for skip matching
        $scopeText = strtolower(implode(' ', array_merge($scopeHints, [$urgency])));

        $eligible = [];

        foreach ($nodes as $node) {
            // Skip questions whose subject is already covered in scope_hints
            $shouldSkip = false;
            foreach ($node['skip_if'] as $keyword) {
                if (str_contains($scopeText, strtolower($keyword))) {
                    $shouldSkip = true;
                    break;
                }
            }

            // Also skip the urgency question if urgency is already known
            if ($node['id'] === 'urgency' && in_array($urgency, ['urgent', 'scheduled'], true)) {
                $shouldSkip = true;
            }

            if (!$shouldSkip) {
                $eligible[] = $node;
            }
        }

        // Sort by weight descending → take top $limit
        usort($eligible, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        return array_values(array_map(fn ($n) => [
            'id'      => $n['id'],
            'text'    => $n['text'],
            'type'    => $n['type'],
            'options' => $n['options'],
        ], array_slice($eligible, 0, $limit)));
    }
}
