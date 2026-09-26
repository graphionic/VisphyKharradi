<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * PackageSeeder — Seeds real production FTPRENEUR health & performance packages
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name'              => 'Personalised Health Coaching',
                'slug'              => 'personalised-health',
                'short_description' => 'Comprehensive health assessment and 1:1 tailored nutrition, movement, and habit protocols.',
                'full_description'  => '<p>Our foundational health optimization program combining deep metabolic profiling, lifestyle coaching, and continuous support to restore energy, vitality, and systemic wellness.</p><p>We build your plan around your daily life, routine, and preferences—ensuring sustainable changes that stick long after the program ends.</p>',
                'regular_price'     => '18000.00',
                'selling_price'     => '14999.00',
                'duration_value'    => 12,
                'duration_unit'     => 'weeks',
                'badge'             => 'Best Value',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/personalised-health.jpg',
                'display_order'     => 1,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    '1:1 Comprehensive Health Assessment',
                    'Customized Nutrition Plan (Indian & Global Options)',
                    'Bi-weekly Dedicated Progress Reviews',
                    'Direct WhatsApp Support & Follow-up',
                    'Lifestyle, Sleep & Habit Architecture Guidance',
                ],
            ],
            [
                'name'              => 'Executive Performance Protocol',
                'slug'              => 'executive-performance',
                'short_description' => 'High-impact health and performance optimization for busy executives and business leaders.',
                'full_description'  => '<p>Designed specifically for time-pressed leaders seeking peak cognitive performance, stress resilience, and sustainable physical endurance without burnout.</p><p>Includes executive travel nutrition guides, high-efficiency workout programming, and priority 1:1 consultation access.</p>',
                'regular_price'     => '25000.00',
                'selling_price'     => '19999.00',
                'duration_value'    => 12,
                'duration_unit'     => 'weeks',
                'badge'             => 'Signature Program',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/executive-performance.jpg',
                'display_order'     => 2,
                'is_featured'       => 1,
                'is_active'         => 1,
                'features'          => [
                    'Comprehensive Executive Lifestyle Audit',
                    'Travel & Dining Out Meal Protocol',
                    'Stress Resilience & Sleep Optimization',
                    'Weekly 1:1 Direct Strategy Sessions',
                    'Priority Messaging & Concierge Support',
                ],
            ],
            [
                'name'              => 'Metabolic Health & Reversal',
                'slug'              => 'metabolic-reversal',
                'short_description' => 'Targeted lifestyle interventions for insulin sensitivity, glucose balance, and body composition.',
                'full_description'  => '<p>A science-backed protocol focusing on dietary structure, targeted resistance work, and metabolic habit building for long-term health sustainability.</p><p>Works alongside medical guidance to optimize metabolic biomarkers, daily energy consistency, and body composition.</p>',
                'regular_price'     => '22000.00',
                'selling_price'     => '17999.00',
                'duration_value'    => 12,
                'duration_unit'     => 'weeks',
                'badge'             => 'Recommended',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/metabolic-reversal.jpg',
                'display_order'     => 3,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Glucose Response & Metabolic Pattern Tracking',
                    'Personalised Food Structure & Meal Timing',
                    'Strength & Daily Movement Blueprint',
                    'Bi-weekly Biomarker & Progress Analysis',
                ],
            ],
            [
                'name'              => 'Gut Microbiome & Digestive Health',
                'slug'              => 'gut-microbiome',
                'short_description' => 'Gut-first restorative protocol for digestion, nutrient absorption, and immunity.',
                'full_description'  => '<p>Systemic digestive reset helping eliminate bloat, restore gut barrier function, and establish microbiome diversity through whole foods and lifestyle strategy.</p><p>Addresses gut-brain axis balance, food sensitivities, and sustainable digestive comfort.</p>',
                'regular_price'     => '16000.00',
                'selling_price'     => '12999.00',
                'duration_value'    => 8,
                'duration_unit'     => 'weeks',
                'badge'             => 'Popular',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/gut-microbiome.jpg',
                'display_order'     => 4,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Gut Symptom & Trigger Food Audit',
                    'Elimination & Systematic Reintroduction Guide',
                    'Microbiome-Friendly Meal Structure',
                    'Weekly Check-ins & Digestive Tracking',
                ],
            ],
            [
                'name'              => 'Hormonal Balance & Vitality',
                'slug'              => 'hormonal-balance',
                'short_description' => 'Personalised lifestyle framework for endocrine health, energy, and mood stability.',
                'full_description'  => '<p>Integrated nutritional and lifestyle coaching designed to support thyroid, adrenal, and reproductive hormone harmony.</p><p>Helps balance energy dips, manage stress impact, and foster sustainable daily vitality.</p>',
                'regular_price'     => '19000.00',
                'selling_price'     => '15999.00',
                'duration_value'    => 12,
                'duration_unit'     => 'weeks',
                'badge'             => 'Recommended',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/hormonal-balance.jpg',
                'display_order'     => 5,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Hormone & Cycle-Aware Food Planning',
                    'Sleep Architecture & Circadian Alignment',
                    'Targeted Low-Stress Movement Guidance',
                    'Bi-weekly Dedicated Coaching Sessions',
                ],
            ],
            [
                'name'              => 'Longevity & Anti-Aging Optimization',
                'slug'              => 'longevity-optimization',
                'short_description' => 'Long-term preventive protocol targeting cellular health, mobility, and lifespan expansion.',
                'full_description'  => '<p>Advanced preventive health framework combining strength preservation, mitochondrial support, and anti-inflammatory lifestyle habits for long-term healthspan expansion.</p>',
                'regular_price'     => '32000.00',
                'selling_price'     => '27999.00',
                'duration_value'    => 24,
                'duration_unit'     => 'weeks',
                'badge'             => 'Premium Protocol',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/longevity-optimization.jpg',
                'display_order'     => 6,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Bi-weekly 1:1 Longevity Consultations',
                    'Cellular Health & Movement Plan',
                    'Strength & Bone Density Focus Routine',
                    'Continuous Lifestyle & Recovery Support',
                ],
            ],
            [
                'name'              => 'Sports Performance & Conditioning',
                'slug'              => 'sports-performance',
                'short_description' => 'Structured strength, mobility, and recovery protocol for endurance and power athletes.',
                'full_description'  => '<p>Targeted periodized training and nutrition support tailored to athletic goals, peak performance, and injury prevention.</p>',
                'regular_price'     => '15000.00',
                'selling_price'     => '11999.00',
                'duration_value'    => 10,
                'duration_unit'     => 'weeks',
                'badge'             => 'Popular',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/sports-performance.jpg',
                'display_order'     => 7,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Performance & Movement Assessment',
                    'Periodized Strength & Athletic Training Plan',
                    'Post-Workout Recovery & Hydration Protocol',
                    'Weekly Metric Reviews',
                ],
            ],
            [
                'name'              => 'Postpartum Restoration & Wellness',
                'slug'              => 'postpartum-restoration',
                'short_description' => 'Gentle, safe, and supportive health rebuild for new mothers.',
                'full_description'  => '<p>Nurturing health guidance tailored for postnatal recovery, pelvic stability, energy restoration, and balanced nutrition.</p>',
                'regular_price'     => '14000.00',
                'selling_price'     => '10999.00',
                'duration_value'    => 8,
                'duration_unit'     => 'weeks',
                'badge'             => 'Special Care',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/postpartum-restoration.jpg',
                'display_order'     => 8,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Postpartum Safety & Energy Audit',
                    'Gentle Core & Functional Movement Guidance',
                    'Nourishing Meal Framework for New Mothers',
                    'Flexible Check-in Schedule',
                ],
            ],
            [
                'name'              => 'Burnout Recovery & Stress Reset',
                'slug'              => 'burnout-recovery',
                'short_description' => 'Restorative lifestyle protocol for nervous system balance, sleep, and fatigue.',
                'full_description'  => '<p>A calming, structured health reboot focused on nervous system downregulation, restorative sleep architecture, and sustainable work-life balance.</p>',
                'regular_price'     => '12000.00',
                'selling_price'     => '8999.00',
                'duration_value'    => 6,
                'duration_unit'     => 'weeks',
                'badge'             => 'Essential',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/burnout-recovery.jpg',
                'display_order'     => 9,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Nervous System Hygiene Plan',
                    'Sleep Architecture & Night Routine Audit',
                    'Low-Barrier Daily Movement Protocol',
                    'Weekly Check-ins',
                ],
            ],
            [
                'name'              => 'Bridal Transformation Protocol',
                'slug'              => 'bridal-transformation',
                'short_description' => 'Tailored timeline-based health, skin glow, and body composition program.',
                'full_description'  => '<p>Custom-timed health and radiance protocol designed to help brides look and feel their absolute best on their special day.</p>',
                'regular_price'     => '21000.00',
                'selling_price'     => '16999.00',
                'duration_value'    => 12,
                'duration_unit'     => 'weeks',
                'badge'             => 'Popular',
                'cta_label'         => 'Get Started',
                'featured_image'    => 'uploads/packages/bridal-transformation.jpg',
                'display_order'     => 10,
                'is_featured'       => 0,
                'is_active'         => 1,
                'features'          => [
                    'Timeline-Mapped Nutrition & Fitness Plan',
                    'Skin & Gut Radiance Protocol',
                    'Targeted Tone & Strength Guidance',
                    'Weekly Progress & Fitting Check-ins',
                ],
            ],
        ];

        foreach ($packages as $pkg) {
            $features = $pkg['features'];
            unset($pkg['features']);

            $existing = $this->db->table('packages')->where('slug', $pkg['slug'])->get()->getRowArray();
            if ($existing) {
                $packageId = (int) $existing['id'];
                $this->db->table('packages')->where('id', $packageId)->update(array_merge($pkg, [
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            } else {
                $pkg['created_at'] = date('Y-m-d H:i:s');
                $pkg['updated_at'] = date('Y-m-d H:i:s');
                $this->db->table('packages')->insert($pkg);
                $packageId = (int) $this->db->insertID();
            }

            // Sync features
            $this->db->table('package_features')->where('package_id', $packageId)->delete();
            $seq = 1;
            foreach ($features as $fText) {
                $this->db->table('package_features')->insert([
                    'package_id'    => $packageId,
                    'feature_text'  => $fText,
                    'display_order' => $seq++,
                    'is_active'     => 1,
                ]);
            }
        }

        echo "PackageSeeder completed: " . count($packages) . " packages seeded successfully.\n";
    }
}
