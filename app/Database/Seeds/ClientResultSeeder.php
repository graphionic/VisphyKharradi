<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * ClientResultSeeder — Pre-Phase 08: Seeds 8 Realistic Demo Client Results
 *
 * Safe for development environments. Fully idempotent.
 * Maintains ZERO FOREIGN KEYS compliance.
 */
class ClientResultSeeder extends Seeder
{
    public function run(): void
    {
        $demoResults = [
            [
                'client_display_name'   => 'Aarav M.',
                'client_subtitle'       => 'Business Owner, 42',
                'package_slug'          => 'personalised-health',
                'fallback_program_name' => 'Personalised Health Coaching',
                'journey_duration'      => '16 Weeks',
                'short_testimonial'     => 'I finally had a plan that worked around my lifestyle instead of forcing my lifestyle around the plan.',
                'full_story'            => '<p>As a busy business owner managing multiple teams and back-to-back schedules, keeping up with strict diets and lengthy workouts always proved unsustainable. Previous health attempts collapsed whenever travel or intense work seasons hit.</p><p>Working through structured, progressive lifestyle adjustments helped align daily nutrition and physical activity directly with my high-pressure routines. We prioritized flexible meal frameworks, sustainable daily movement targets, and stress management.</p><p>Over the course of 16 weeks, energy levels stabilized noticeably, sleep quality improved, and key health markers showed steady, consistent progress without requiring extreme restrictions.</p>',
                'display_order'         => 1,
                'is_featured'           => 1,
                'is_active'             => 1,
                'focus_areas'           => ['WEIGHT LOSS', 'METABOLIC HEALTH', 'DIABETIC CONTROL', 'ENERGY'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '92', 'after_value' => '78', 'unit' => 'kg', 'context' => 'Recorded at the start and end of the 16-week program.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'HbA1c', 'before_value' => '8.2', 'after_value' => '6.4', 'unit' => '%', 'context' => 'Demo value entered for interface testing.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '42', 'after_value' => '36', 'unit' => 'in', 'context' => 'Abdominal circumference measurement.', 'display_order' => 3, 'is_public' => 1],
                    ['metric_name' => 'Internal Review Score', 'before_value' => '4/10', 'after_value' => '9/10', 'unit' => 'points', 'context' => 'Private internal assessment score.', 'display_order' => 4, 'is_public' => 0],
                ],
            ],
            [
                'client_display_name'   => 'Meera S.',
                'client_subtitle'       => 'Marketing Professional, 36',
                'package_slug'          => 'metabolic-reversal',
                'fallback_program_name' => 'Metabolic Health & Reversal',
                'journey_duration'      => '20 Weeks',
                'short_testimonial'     => 'The biggest change was understanding what I could actually sustain instead of repeatedly starting over.',
                'full_story'            => '<p>Long desk hours and unpredictable campaign deadlines led to an increasingly sedentary lifestyle and irregular meal patterns. I found myself trapped in a cycle of starting intense wellness routines only to abandon them weeks later.</p><p>The program focused on foundational metabolic habits rather than short-term fixes. We introduced balanced meal structures, progressive resistance sessions, and sustainable daily activity goals that naturally fit a corporate schedule.</p><p>By focusing on consistency over intensity, I built a reliable daily rhythm. Over 20 weeks, physical stamina increased, daily focus improved, and key lipid parameters reflected positive trends.</p>',
                'display_order'         => 2,
                'is_featured'           => 1,
                'is_active'             => 1,
                'focus_areas'           => ['CHOLESTEROL', 'METABOLIC HEALTH', 'WEIGHT MANAGEMENT', 'LIFESTYLE'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '84', 'after_value' => '73', 'unit' => 'kg', 'context' => 'Measured over 20 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Total Cholesterol', 'before_value' => '245', 'after_value' => '188', 'unit' => 'mg/dL', 'context' => 'Demo values entered for testing.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '39', 'after_value' => '34', 'unit' => 'in', 'context' => 'Measured at natural waistline.', 'display_order' => 3, 'is_public' => 1],
                    ['metric_name' => 'Private Baseline Note', 'before_value' => 'Initial Audit', 'after_value' => 'Completed', 'unit' => null, 'context' => 'Private coach review note.', 'display_order' => 4, 'is_public' => 0],
                ],
            ],
            [
                'client_display_name'   => 'Rohan K.',
                'client_subtitle'       => 'Technology Consultant, 39',
                'package_slug'          => 'executive-performance',
                'fallback_program_name' => 'Executive Performance Protocol',
                'journey_duration'      => '12 Weeks',
                'short_testimonial'     => 'The structure gave me clarity. I knew what to focus on each week without turning fitness into another full-time job.',
                'full_story'            => '<p>Frequent travel, late-night client calls, and constant context switching left little energy for consistent strength training or mindful nutrition. I wanted an efficient system that maximized results without taking over my week.</p><p>We designed a high-efficiency performance blueprint incorporating targeted resistance workouts, streamlined meal choices for travel, and circadian alignment practices for better sleep recovery.</p><p>Within 12 weeks, body composition improved significantly, energy crashes vanished, and lifting performance reached personal bests despite a demanding work itinerary.</p>',
                'display_order'         => 3,
                'is_featured'           => 0,
                'is_active'             => 1,
                'focus_areas'           => ['STRENGTH', 'ENERGY', 'BODY COMPOSITION', 'PERFORMANCE'],
                'metrics'               => [
                    ['metric_name' => 'Body Weight', 'before_value' => '88', 'after_value' => '82', 'unit' => 'kg', 'context' => 'Measured over 12 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '38', 'after_value' => '34.5', 'unit' => 'in', 'context' => 'Waist circumference reduction.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Deadlift', 'before_value' => '90', 'after_value' => '130', 'unit' => 'kg', 'context' => 'Strength marker progress.', 'display_order' => 3, 'is_public' => 1],
                ],
            ],
            [
                'client_display_name'   => 'Nisha P.',
                'client_subtitle'       => 'Entrepreneur, 44',
                'package_slug'          => 'gut-microbiome',
                'fallback_program_name' => 'Gut Microbiome & Digestive Health',
                'journey_duration'      => '14 Weeks',
                'short_testimonial'     => 'For the first time, the plan felt personal enough to fit my food preferences, schedule and everyday routine.',
                'full_story'            => '<p>Managing an expanding startup meant irregular meal times, frequent eating on the go, and persistent post-meal discomfort that impacted daily productivity and energy levels.</p><p>The coaching approach focused on a systematic gut reset—identifying personal food triggers, structuring balanced whole-food meals, and implementing mindful eating and stress management techniques.</p><p>Over 14 weeks, digestive comfort improved dramatically, afternoon sluggishness was replaced by sustained energy, and healthy eating became second nature.</p>',
                'display_order'         => 4,
                'is_featured'           => 0,
                'is_active'             => 1,
                'focus_areas'           => ['DIGESTIVE HEALTH', 'NUTRITION', 'ENERGY', 'LIFESTYLE'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '71', 'after_value' => '66', 'unit' => 'kg', 'context' => 'Measured over 14 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '34', 'after_value' => '31.5', 'unit' => 'in', 'context' => 'Midsection change.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Daily Energy Rating', 'before_value' => '5/10', 'after_value' => '8/10', 'unit' => null, 'context' => 'Self-reported daily vitality rating.', 'display_order' => 3, 'is_public' => 1],
                ],
            ],
            [
                'client_display_name'   => 'Vikram D.',
                'client_subtitle'       => 'Finance Professional, 48',
                'package_slug'          => 'personalised-health',
                'fallback_program_name' => 'Personalised Health Coaching',
                'journey_duration'      => '24 Weeks',
                'short_testimonial'     => 'The process was gradual, practical and measurable. That made it much easier for me to stay consistent.',
                'full_story'            => '<p>Years of long desk hours and minimal exercise had resulted in weight gain and elevated cardiovascular risk markers. Previous attempts at rigid fitness programs quickly fell apart under work stress.</p><p>We established a 24-week progressive health strategy centered around heart-healthy nutrition, low-impact cardio, structured strength training, and regular habit monitoring.</p><p>Steady, deliberate adjustments led to meaningful improvements in body weight, waist circumference, and blood pressure readings over the 6-month journey.</p>',
                'display_order'         => 5,
                'is_featured'           => 1,
                'is_active'             => 1,
                'focus_areas'           => ['WEIGHT LOSS', 'BLOOD PRESSURE', 'STRENGTH', 'SUSTAINABLE HABITS'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '101', 'after_value' => '86', 'unit' => 'kg', 'context' => 'Recorded over 24 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '44', 'after_value' => '38', 'unit' => 'in', 'context' => 'Abdominal measurement reduction.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Blood Pressure', 'before_value' => '148/94', 'after_value' => '128/82', 'unit' => 'mmHg', 'context' => 'Demo resting readings logged for UI testing.', 'display_order' => 3, 'is_public' => 1],
                ],
            ],
            [
                'client_display_name'   => 'Isha R.',
                'client_subtitle'       => 'Creative Director, 33',
                'package_slug'          => 'hormonal-balance',
                'fallback_program_name' => 'Hormonal Balance & Vitality',
                'journey_duration'      => '18 Weeks',
                'short_testimonial'     => 'I stopped chasing extreme plans and started building habits I could actually keep.',
                'full_story'            => '<p>After trying multiple fad diets and intense fitness bootcamps, I constantly struggled with fatigue, mood fluctuations, and difficulty building lean strength.</p><p>The coaching prioritized endocrine-friendly nutrition, cycle-aware strength training, and recovery protocols. We shifted the focus from burning calories to building strength and supporting metabolic health.</p><p>Through 18 weeks of consistent, balanced efforts, my energy stabilized, body composition lean muscle ratio improved, and confidence in the gym increased.</p>',
                'display_order'         => 6,
                'is_featured'           => 0,
                'is_active'             => 1,
                'focus_areas'           => ['BODY COMPOSITION', 'STRENGTH', 'NUTRITION', 'ENERGY'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '69', 'after_value' => '63', 'unit' => 'kg', 'context' => 'Logged over 18 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '32', 'after_value' => '29', 'unit' => 'in', 'context' => 'Waist measurement change.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Squat', 'before_value' => '35', 'after_value' => '60', 'unit' => 'kg', 'context' => 'Barbell squat progress.', 'display_order' => 3, 'is_public' => 1],
                ],
            ],
            [
                'client_display_name'   => 'Kabir T.',
                'client_subtitle'       => 'Sales Director, 45',
                'package_slug'          => 'executive-performance',
                'fallback_program_name' => 'Executive Performance Protocol',
                'journey_duration'      => '16 Weeks',
                'short_testimonial'     => 'I needed something structured but realistic enough to survive travel, meetings and unpredictable weeks.',
                'full_story'            => '<p>As a sales director traveling frequently across regions, maintaining any fitness routine felt nearly impossible. Hotel gyms, late dinners, and long flights disrupted consistency.</p><p>We built an adaptable lifestyle framework—focusing on bodyweight and hotel gym workouts, smart eating strategies during travel, and simple hydration and sleep habits.</p><p>Over 16 weeks, physical stamina increased significantly, posture and mobility improved, and body fat decreased despite a heavy travel schedule.</p>',
                'display_order'         => 7,
                'is_featured'           => 0,
                'is_active'             => 1,
                'focus_areas'           => ['STRENGTH', 'MOBILITY', 'ENERGY', 'BODY COMPOSITION'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '94', 'after_value' => '87', 'unit' => 'kg', 'context' => 'Recorded over 16 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '40', 'after_value' => '36', 'unit' => 'in', 'context' => 'Midsection progress.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Push-Ups', 'before_value' => '12', 'after_value' => '32', 'unit' => 'reps', 'context' => 'Upper body endurance test.', 'display_order' => 3, 'is_public' => 1],
                ],
            ],
            [
                'client_display_name'   => 'Ananya V.',
                'client_subtitle'       => 'Product Manager, 31',
                'package_slug'          => 'personalised-health',
                'fallback_program_name' => 'Personalised Health Coaching',
                'journey_duration'      => '12 Weeks',
                'short_testimonial'     => 'The plan felt flexible without feeling vague. I always knew what the next practical step was.',
                'full_story'            => '<p>Working remotely often led to blurred boundaries between work and personal life, resulting in irregular eating hours, extended screen time, and low daily physical activity.</p><p>We introduced a structured daily routine with balanced meal planning, progressive home strength sessions, and regular movement breaks throughout the workday.</p><p>In 12 weeks, body composition improved, core strength and endurance increased, and a sustainable work-life balance was established.</p>',
                'display_order'         => 8,
                'is_featured'           => 0,
                'is_active'             => 1,
                'focus_areas'           => ['NUTRITION', 'BODY COMPOSITION', 'STRENGTH', 'LIFESTYLE'],
                'metrics'               => [
                    ['metric_name' => 'Weight', 'before_value' => '67', 'after_value' => '61', 'unit' => 'kg', 'context' => 'Recorded over 12 weeks.', 'display_order' => 1, 'is_public' => 1],
                    ['metric_name' => 'Waist', 'before_value' => '31', 'after_value' => '28.5', 'unit' => 'in', 'context' => 'Waist measurement change.', 'display_order' => 2, 'is_public' => 1],
                    ['metric_name' => 'Plank Hold', 'before_value' => '45', 'after_value' => '105', 'unit' => 'seconds', 'context' => 'Core endurance test.', 'display_order' => 3, 'is_public' => 1],
                ],
            ],
        ];

        $db = \Config\Database::connect();

        foreach ($demoResults as $item) {
            $db->transStart();

            // Resolve package_id and program_name_snapshot
            $packageId = null;
            $programSnapshot = $item['fallback_program_name'];

            if (!empty($item['package_slug'])) {
                $pkg = $db->table('packages')
                    ->where('slug', $item['package_slug'])
                    ->where('deleted_at IS NULL', null, false)
                    ->get()
                    ->getRowArray();

                if ($pkg) {
                    $packageId = (int) $pkg['id'];
                    $programSnapshot = $pkg['name'];
                }
            }

            // Check if demo Client Result record already exists
            $existing = $db->table('client_results')
                ->where('client_display_name', $item['client_display_name'])
                ->where('deleted_at IS NULL', null, false)
                ->get()
                ->getRowArray();

            $masterData = [
                'package_id'            => $packageId,
                'program_name_snapshot' => $programSnapshot,
                'client_display_name'   => $item['client_display_name'],
                'client_subtitle'       => $item['client_subtitle'],
                'short_testimonial'     => $item['short_testimonial'],
                'full_story'            => $item['full_story'],
                'journey_duration'      => $item['journey_duration'],
                'cover_image'           => null,
                'display_order'         => $item['display_order'],
                'is_featured'           => $item['is_featured'],
                'is_active'             => $item['is_active'],
                'updated_at'            => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $resultId = (int) $existing['id'];
                $db->table('client_results')->where('id', $resultId)->update($masterData);
            } else {
                $masterData['created_at'] = date('Y-m-d H:i:s');
                $db->table('client_results')->insert($masterData);
                $resultId = (int) $db->insertID();
            }

            // Sync Focus Areas (delete & re-insert for clean idempotency)
            $db->table('client_result_focus_areas')->where('client_result_id', $resultId)->delete();
            $focusBatch = [];
            foreach ($item['focus_areas'] as $idx => $label) {
                $focusBatch[] = [
                    'client_result_id' => $resultId,
                    'label'            => $label,
                    'display_order'    => $idx,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'updated_at'       => date('Y-m-d H:i:s'),
                ];
            }
            if (!empty($focusBatch)) {
                $db->table('client_result_focus_areas')->insertBatch($focusBatch);
            }

            // Sync Outcome Metrics (delete & re-insert for clean idempotency)
            $db->table('client_result_metrics')->where('client_result_id', $resultId)->delete();
            $metricBatch = [];
            foreach ($item['metrics'] as $m) {
                $metricBatch[] = [
                    'client_result_id'       => $resultId,
                    'metric_name'            => $m['metric_name'],
                    'before_value'           => $m['before_value'],
                    'after_value'            => $m['after_value'],
                    'unit'                   => $m['unit'],
                    'context'                => $m['context'],
                    'measurement_start_date' => null,
                    'measurement_end_date'   => null,
                    'display_order'          => $m['display_order'],
                    'is_public'              => $m['is_public'],
                    'created_at'             => date('Y-m-d H:i:s'),
                    'updated_at'             => date('Y-m-d H:i:s'),
                ];
            }
            if (!empty($metricBatch)) {
                $db->table('client_result_metrics')->insertBatch($metricBatch);
            }

            $db->transComplete();
        }

        echo "ClientResultSeeder completed: 8 demo Client Results seeded successfully.\n";
    }
}
