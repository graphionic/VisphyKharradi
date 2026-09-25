<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\PackageModel;
use App\Models\PackageFeatureModel;
use App\Models\PackageOptionModel;
use App\Models\PackageOptionFeatureModel;

class SeedRealPackages extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:seed-packages';
    protected $description = 'Seeds 10 realistic packages for Visphy Kharradi with options and features.';

    public function run(array $params)
    {
        CLI::write("Seeding 10 Real Packages for Visphy Kharradi...", 'yellow');

        $packageModel       = new PackageModel();
        $featureModel       = new PackageFeatureModel();
        $optionModel        = new PackageOptionModel();
        $optionFeatureModel = new PackageOptionFeatureModel();

        // Clear existing packages if desired or truncate
        $db = \Config\Database::connect();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        $db->query("TRUNCATE TABLE package_option_features");
        $db->query("TRUNCATE TABLE package_options");
        $db->query("TRUNCATE TABLE package_features");
        $db->query("TRUNCATE TABLE packages");
        $db->query("SET FOREIGN_KEY_CHECKS = 1");

        $now = date('Y-m-d H:i:s');

        $packages = [
            [
                'name' => 'Personalised Health Management Program',
                'slug' => 'personalised-health-management',
                'short_description' => 'A tailor-made medical nutrition and body transformation program designed specifically for your bio-individual goals.',
                'full_description' => '<p>Our flagship <strong>Personalised Health Management Program</strong> combines comprehensive metabolic evaluation with custom nutrition, strength conditioning, and continuous lifestyle tracking.</p><h3>What to expect:</h3><ul><li>Bi-weekly 1-on-1 strategy sessions with Visphy Kharradi</li><li>Customized non-restrictive nutrition blueprints</li><li>Progressive strength & resistance training programming</li><li>Continuous biometric monitoring & bloodwork analysis</li></ul>',
                'badge' => 'recommended',
                'selling_price' => 50000.00,
                'regular_price' => 60000.00,
                'duration_value' => 3,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/personalised-health.jpg',
                'is_active' => 1,
                'is_featured' => 1,
                'display_order' => 10,
                'cta_label' => 'Start Your Transformation',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-sample/viewform',
                'whatsapp_template' => 'Hi {customer_name}, welcome to your Personalised Health Program ({package_name}). Your consultation with Visphy Kharradi is being scheduled.',
                'package_features' => [
                    'Complete Metabolic Health Assessment',
                    'Custom Non-Restrictive Meal Protocol',
                    'Personalized Workout & Conditioning Routine',
                    'Dedicated VIP WhatsApp Support',
                ],
                'options' => [
                    [
                        'name' => '3 Month Foundations',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 50000.00,
                        'short_description' => 'Ideal for initial health reset, weight optimization, and building sustainable daily habits.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Personalised nutrition & meal blueprint',
                            'Strength training & cardio plan',
                            '2 Video calls with Visphy Kharradi',
                            'Bi-weekly progress check-ins',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '6 Month Deep Transformation',
                        'duration_value' => 6,
                        'duration_unit' => 'month',
                        'price' => 80000.00,
                        'short_description' => 'Comprehensive metabolic shift, body composition overhaul, and long-term habit mastery.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Advanced bio-individual nutrition & meal updates',
                            'Progressive periodized strength programming',
                            '4 Video calls with Visphy Kharradi',
                            'Bloodwork & biomarker analysis review',
                            'Priority WhatsApp concierge support'
                        ]
                    ],
                    [
                        'name' => '12 Month Mastery & Longevity',
                        'duration_value' => 1,
                        'duration_unit' => 'year',
                        'price' => 140000.00,
                        'short_description' => 'Full-year dedicated health partnership for lifelong vigor, metabolic age reduction, and peak fitness.',
                        'sort_order' => 3,
                        'is_active' => 1,
                        'features' => [
                            'Complete year-long health & performance strategy',
                            '8 Video calls with Visphy Kharradi',
                            'Quarterly lipid & metabolic biomarker tracking',
                            'Custom travel & holiday nutrition protocols',
                            '24/7 Unlimited VIP Direct Concierge'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Executive High-Performance Leadership',
                'slug' => 'executive-high-performance-leadership',
                'short_description' => 'Precision bio-coaching for C-suite leaders and founders to maximize cognitive clarity, stamina, and stress resilience.',
                'full_description' => '<p>Designed exclusively for high-stress executives, entrepreneurs, and leaders. The <strong>Executive High-Performance Program</strong> optimizes your energy architecture, sleep quality, and decision-making bandwidth.</p><h3>Program Highlights:</h3><ul><li>Wearable data integration (Oura, Apple Watch, Garmin)</li><li>Travel-proof dining & business dinner strategies</li><li>Cortisol & circadian rhythm alignment</li><li>Direct instant access to Visphy Kharradi</li></ul>',
                'badge' => 'popular',
                'selling_price' => 75000.00,
                'regular_price' => 90000.00,
                'duration_value' => 3,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/executive-performance.jpg',
                'is_active' => 1,
                'is_featured' => 1,
                'display_order' => 20,
                'cta_label' => 'Apply for Executive Coaching',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-exec/viewform',
                'whatsapp_template' => 'Hello {customer_name}, your Executive High-Performance onboarding details are ready for review.',
                'package_features' => [
                    'Executive Bio-Energy & Stamina Protocol',
                    'Travel & Hotel Dining Master Guide',
                    'Wearable Sleep & Recovery Optimization',
                    'Executive Concierge VIP Channel',
                ],
                'options' => [
                    [
                        'name' => '3 Month Executive Sprint',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 75000.00,
                        'short_description' => 'Rapid energy architecture upgrade and stress mitigation for intense work phases.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Executive stamina & focus nutrition protocol',
                            '3 1-on-1 strategy sessions with Visphy Kharradi',
                            'Wearable sleep & recovery analysis',
                            'Business travel & airport dining blueprint',
                            'Direct WhatsApp access'
                        ]
                    ],
                    [
                        'name' => '6 Month C-Suite Performance',
                        'duration_value' => 6,
                        'duration_unit' => 'month',
                        'price' => 125000.00,
                        'short_description' => 'Complete executive health overhaul to build sustainable peak cognitive output and physical power.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Comprehensive Executive Bio-Hacking Protocol',
                            '6 1-on-1 strategy sessions with Visphy Kharradi',
                            'Continuous HRV & Sleep Architecture Tracking',
                            'Executive Jet Lag & Timezone Reset Guide',
                            'Priority 24/7 VIP Concierge Channel'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Diabetes & Metabolic Reversal Program',
                'slug' => 'diabetes-metabolic-reversal',
                'short_description' => 'Evidence-based clinical nutrition protocol to restore insulin sensitivity and reverse Type-2 Diabetes naturally.',
                'full_description' => '<p>Reverse insulin resistance and take control of your blood glucose levels with our clinically guided <strong>Metabolic Reversal Program</strong>.</p><h3>Key Outcomes:</h3><ul><li>Significant HbA1c reduction within 90 days</li><li>Continuous Glucose Monitor (CGM) real-time feedback</li><li>Medication tapering support with your physician</li><li>Sustainable low-glycemic dietary protocols</li></ul>',
                'badge' => 'best_value',
                'selling_price' => 45000.00,
                'regular_price' => 55000.00,
                'duration_value' => 3,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/metabolic-reversal.jpg',
                'is_active' => 1,
                'is_featured' => 1,
                'display_order' => 30,
                'cta_label' => 'Reverse Diabetes Now',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-diabetes/viewform',
                'whatsapp_template' => 'Hi {customer_name}, welcome to the Diabetes & Metabolic Reversal Program. Let us analyze your baseline glucose metrics.',
                'package_features' => [
                    'CGM Real-time Glycemic Response Mapping',
                    'Insulin Sensitivity Restoration Protocol',
                    'Weekly Blood Sugar Log Analysis',
                    'Medication Tapering Coordination Guide',
                ],
                'options' => [
                    [
                        'name' => '3 Month Glycemic Reset',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 45000.00,
                        'short_description' => 'Targeted 90-day protocol for rapid glucose stabilization and insulin sensitivity enhancement.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'CGM placement & glucose response analysis',
                            'Customized Low-Glycemic Index Meal Plan',
                            '3 Video Calls with Visphy Kharradi',
                            'Weekly glucose trend reviews',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '6 Month Complete Metabolic Reversal',
                        'duration_value' => 6,
                        'duration_unit' => 'month',
                        'price' => 75000.00,
                        'short_description' => 'Full metabolic restoration aiming for long-term remission, HbA1c normalization, and medication freedom.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Full metabolic panel & fasting insulin tracking',
                            '6 Video Calls with Visphy Kharradi',
                            'Physician-coordinated medication tapering support',
                            'Pancreatic beta-cell recovery nutrition protocol',
                            'Dedicated priority WhatsApp support'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Gut Health & Microbiome Reset',
                'slug' => 'gut-health-microbiome-reset',
                'short_description' => 'Eliminate bloating, IBS symptoms, and leaky gut while rebuilding a thriving, balanced intestinal microbiome.',
                'full_description' => '<p>Your gut is your second brain. Our <strong>Gut Health & Microbiome Reset</strong> identifies food sensitivities, repairs gut mucosal lining, and repopulates beneficial gut flora.</p><h3>What is included:</h3><ul><li>Structured Elimination & Reintroduction Protocol</li><li>Bloating, acidity, and constipation resolution</li><li>Targeted probiotic & prebiotic supplementation stack</li><li>Gut-brain axis mental clarity optimization</li></ul>',
                'badge' => null,
                'selling_price' => 35000.00,
                'regular_price' => 42000.00,
                'duration_value' => 2,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/gut-microbiome.jpg',
                'is_active' => 1,
                'is_featured' => 0,
                'display_order' => 40,
                'cta_label' => 'Heal Your Gut',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-gut/viewform',
                'whatsapp_template' => 'Hello {customer_name}, let us begin your Gut Health & Microbiome Reset journey.',
                'package_features' => [
                    'Elimination & Reintroduction Protocol',
                    'IBS & Bloating Symptom Tracker',
                    'Microbiome Supplementation Guide',
                    'Anti-Inflammatory Gut Healing Meal Plan',
                ],
                'options' => [
                    [
                        'name' => '2 Month Gut Repair',
                        'duration_value' => 2,
                        'duration_unit' => 'month',
                        'price' => 35000.00,
                        'short_description' => 'Focused gut lining restoration and elimination of active digestive distress.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Elimination & Reintroduction food roadmap',
                            '2 Video Consultations with Visphy Kharradi',
                            'Anti-inflammatory meal & drink recipes',
                            'Symptom tracking & weekly check-in',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '4 Month Microbiome Masterclass',
                        'duration_value' => 4,
                        'duration_unit' => 'month',
                        'price' => 60000.00,
                        'short_description' => 'Deep microbiome re-diversification, food intolerance resolution, and long-term digestive vitality.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Stool & Gut DNA test interpretation guide',
                            '4 Video Consultations with Visphy Kharradi',
                            'Customized digestive enzyme & probiotic stack',
                            'Gut-brain stress resilience routine',
                            'Priority WhatsApp support'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Thyroid & Hormonal Balance Protocol',
                'slug' => 'thyroid-hormonal-balance',
                'short_description' => 'Holistic nutrition and cycle-syncing framework to resolve PCOS, Hypothyroidism, and hormonal imbalances.',
                'full_description' => '<p>Hormones regulate energy, mood, metabolism, and weight. The <strong>Thyroid & Hormonal Balance Protocol</strong> addresses root causes of PCOS, irregular cycles, and sluggish thyroid function.</p><h3>Key Benefits:</h3><ul><li>Natural cycle syncing and hormonal alignment</li><li>Targeted nutrition for T3/T4 conversion</li><li>PCOS insulin resistance reduction</li><li>Restored sleep, mood, and skin radiance</li></ul>',
                'badge' => null,
                'selling_price' => 40000.00,
                'regular_price' => 48000.00,
                'duration_value' => 3,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/hormonal-balance.jpg',
                'is_active' => 1,
                'is_featured' => 0,
                'display_order' => 50,
                'cta_label' => 'Balance Your Hormones',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-hormone/viewform',
                'whatsapp_template' => 'Hi {customer_name}, welcome to the Thyroid & Hormonal Balance Protocol.',
                'package_features' => [
                    'Comprehensive Hormone Panel Interpretation',
                    'Cycle-Syncing Nutrition & Exercise Guide',
                    'PCOS & Thyroid Metabolic Support',
                    'Natural Anti-Inflammatory Meal Plans',
                ],
                'options' => [
                    [
                        'name' => '3 Month Hormonal Reset',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 40000.00,
                        'short_description' => 'Initial hormone stabilization, cycle regularization, and symptom relief.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Thyroid (TSH, T3, T4) & PCOS evaluation',
                            '2 Video Consultations with Visphy Kharradi',
                            'Hormone-balancing seed cycling & meal plan',
                            'Circadian rhythm & sleep optimization',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '6 Month Complete Endocrine Harmony',
                        'duration_value' => 6,
                        'duration_unit' => 'month',
                        'price' => 70000.00,
                        'short_description' => 'Deep endocrine restoration for sustained fertility, weight loss, and vibrant energy.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Full hormonal & micronutrient lab review',
                            '5 Video Consultations with Visphy Kharradi',
                            'Cycle-synced resistance training program',
                            'Weight loss plateau breakthrough strategy',
                            'Priority WhatsApp support'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Anti-Aging & Longevity Optimization',
                'slug' => 'anti-aging-longevity-optimization',
                'short_description' => 'Advanced cellular health, autophagy, and telomere preservation protocol to reduce biological age and extend healthspan.',
                'full_description' => '<p>Live longer, live younger. Our <strong>Anti-Aging & Longevity Optimization Program</strong> leverages state-of-the-art longevity research to optimize cellular repair and mitochondrial function.</p><h3>Program Highlights:</h3><ul><li>Biological age clock tracking</li><li>Intermittent fasting & autophagy schedules</li><li>Mitochondrial biogenesis nutrition stack</li><li>DEXA body composition tracking</li></ul>',
                'badge' => 'recommended',
                'selling_price' => 110000.00,
                'regular_price' => 135000.00,
                'duration_value' => 6,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/longevity-optimization.jpg',
                'is_active' => 1,
                'is_featured' => 1,
                'display_order' => 60,
                'cta_label' => 'Optimize Longevity',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-longevity/viewform',
                'whatsapp_template' => 'Greetings {customer_name}, your Anti-Aging & Longevity Optimization journey begins today.',
                'package_features' => [
                    'Biological Age & Epigenetic Biomarker Protocol',
                    'Autophagy & Intermittent Fasting Schedule',
                    'Mitochondrial Energy & Peptide Nutrition Guide',
                    'DEXA & Visceral Fat Tracking',
                ],
                'options' => [
                    [
                        'name' => '6 Month Cellular Refresh',
                        'duration_value' => 6,
                        'duration_unit' => 'month',
                        'price' => 110000.00,
                        'short_description' => 'Rejuvenate cellular health, improve VO2 max, and lower systemic inflammation.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Biological age assessment & biomarker roadmap',
                            '4 Video Consultations with Visphy Kharradi',
                            'Senolytic & NAD+ supportive nutrition stack',
                            'Autophagy-inducing fasting protocol',
                            'Direct WhatsApp concierge'
                        ]
                    ],
                    [
                        'name' => '12 Month Peak Healthspan VIP',
                        'duration_value' => 1,
                        'duration_unit' => 'year',
                        'price' => 195000.00,
                        'short_description' => 'The ultimate longevity partnership for total cellular renewal, peak cognition, and disease prevention.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Full-year biological clock & telomere monitoring',
                            '8 Video Consultations with Visphy Kharradi',
                            'Comprehensive DEXA body composition analysis',
                            'Custom anti-glycation & collagen preservation plan',
                            '24/7 Unlimited Direct Access to Visphy'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Sports Performance & Athletic Conditioning',
                'slug' => 'sports-performance-athletic-conditioning',
                'short_description' => 'Peak athletic performance programming for competitive athletes, runners, lifters, and fitness enthusiasts.',
                'full_description' => '<p>Unlock your true athletic potential. The <strong>Sports Performance & Athletic Conditioning</strong> program optimizes power-to-weight ratio, endurance, recovery speed, and injury prevention.</p><h3>What you get:</h3><ul><li>Periodized strength & explosive conditioning</li><li>Pre-workout, intra-workout, and post-workout fueling</li><li>Lactate threshold & endurance nutrition</li><li>Biomechanical movement screening</li></ul>',
                'badge' => null,
                'selling_price' => 25000.00,
                'regular_price' => 30000.00,
                'duration_value' => 1,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/sports-performance.jpg',
                'is_active' => 1,
                'is_featured' => 0,
                'display_order' => 70,
                'cta_label' => 'Elevate Your Performance',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-sports/viewform',
                'whatsapp_template' => 'Hey {customer_name}, let us gear up for your Sports Performance program.',
                'package_features' => [
                    'Periodized Strength & Athletic Conditioning',
                    'Nutrient Timing & Electrolyte Fueling Strategy',
                    'Recovery & Injury Prevention Protocol',
                    'Performance Benchmark Tracking',
                ],
                'options' => [
                    [
                        'name' => '1 Month Race & Event Fueling',
                        'duration_value' => 1,
                        'duration_unit' => 'month',
                        'price' => 25000.00,
                        'short_description' => 'Targeted 30-day prep for upcoming marathons, competitions, or intense training blocks.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Carb-loading & race day nutrition strategy',
                            '1 Video Call with Visphy Kharradi',
                            'Intra-workout hydration & electrolyte plan',
                            'DOMS reduction & recovery protocol',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '3 Month Athletic Masterclass',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 60000.00,
                        'short_description' => 'Comprehensive athletic development block for significant gains in strength, speed, and lean mass.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Full macro-cycle periodized workout program',
                            '3 Video Calls with Visphy Kharradi',
                            'VO2 Max & metabolic conditioning plan',
                            'Body composition & muscle mass tracking',
                            'Priority WhatsApp support'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Postpartum Body & Wellness Restoration',
                'slug' => 'postpartum-body-wellness-restoration',
                'short_description' => 'Safe, nurturing, and effective postnatal recovery for new mothers looking to heal, rebuild core strength, and reclaim energy.',
                'full_description' => '<p>Congratulations on your journey into motherhood! Our <strong>Postpartum Restoration Program</strong> provides gentle, evidence-based guidance to help your body heal naturally.</p><h3>Program Focus:</h3><ul><li>Diastasis Recti (abdominal separation) safe core rehab</li><li>Pelvic floor strengthening</li><li>Nutrient-dense lactation-safe meal plans</li><li>Postnatal energy recovery & hormonal balance</li></ul>',
                'badge' => 'popular',
                'selling_price' => 42000.00,
                'regular_price' => 50000.00,
                'duration_value' => 3,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/postpartum-restoration.jpg',
                'is_active' => 1,
                'is_featured' => 0,
                'display_order' => 80,
                'cta_label' => 'Begin Postnatal Healing',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-postpartum/viewform',
                'whatsapp_template' => 'Congratulations {customer_name}! Welcome to your Postpartum Wellness Restoration program.',
                'package_features' => [
                    'Diastasis Recti & Pelvic Floor Safe Workouts',
                    'Lactation-Enhancing Nutrition Protocol',
                    'Postnatal Fatigue & Iron Recovery',
                    'Gentle Progressive Body Toning',
                ],
                'options' => [
                    [
                        'name' => '3 Month Gentle Renewal',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 42000.00,
                        'short_description' => 'Nurturing 90-day recovery plan focused on core restoration, milk supply preservation, and stamina.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Diastasis recti & core safety assessment',
                            '3 Video Calls with Visphy Kharradi',
                            'Lactation-safe wholesome meal plans',
                            'Postpartum sleep hygiene guidance',
                            'Warm empathetic WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '6 Month Complete Postnatal Rebuild',
                        'duration_value' => 6,
                        'duration_unit' => 'month',
                        'price' => 72000.00,
                        'short_description' => 'Full postnatal body transformation restoring pre-pregnancy fitness, tone, and confidence.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Complete body composition & muscle tone rebuild',
                            '6 Video Calls with Visphy Kharradi',
                            'Post-weaning hormonal transition nutrition',
                            'Family-friendly easy meal prep ideas',
                            'Priority WhatsApp support'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Stress, Anxiety & Burnout Recovery',
                'slug' => 'stress-anxiety-burnout-recovery',
                'short_description' => 'Restore nervous system balance, lower elevated cortisol, and overcome chronic fatigue with targeted bio-nutrition.',
                'full_description' => '<p>Chronic stress drains your adrenals and suppresses immune function. The <strong>Burnout Recovery Program</strong> uses clinical nutrition, nervous system regulation, and sleep architecture to rebuild your vitality.</p><h3>Key Pillars:</h3><ul><li>Cortisol & Adrenal Fatigue Reset</li><li>Vagus Nerve stimulation techniques</li><li>Deep sleep architecture improvement</li><li>Brain-fog lifting micronutrient protocol</li></ul>',
                'badge' => null,
                'selling_price' => 28000.00,
                'regular_price' => 35000.00,
                'duration_value' => 6,
                'duration_unit' => 'weeks',
                'featured_image' => 'uploads/packages/burnout-recovery.jpg',
                'is_active' => 1,
                'is_featured' => 0,
                'display_order' => 90,
                'cta_label' => 'Restore Your Energy',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-burnout/viewform',
                'whatsapp_template' => 'Hi {customer_name}, let us help you recover your peace, sleep, and vital energy.',
                'package_features' => [
                    'Adrenal & Cortisol Flushing Protocol',
                    'Vagus Nerve & Nervous System Regulation',
                    'Sleep Architecture Improvement Plan',
                    'Anti-Anxiety Micronutrient Stack',
                ],
                'options' => [
                    [
                        'name' => '6 Week Nervous System Reset',
                        'duration_value' => 6,
                        'duration_unit' => 'week',
                        'price' => 28000.00,
                        'short_description' => 'Rapid acute stress relief and sleep restoration block.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Cortisol-lowering nutrition & evening routine',
                            '2 Video Consultations with Visphy Kharradi',
                            'Breathwork & vagal tone audio guides',
                            'Supplements for anxiety & calm focus',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '3 Month Total Burnout Reversal',
                        'duration_value' => 3,
                        'duration_unit' => 'month',
                        'price' => 52000.00,
                        'short_description' => 'Deep adrenal regeneration, long-term resilience building, and vibrant daily energy.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Continuous HRV (Heart Rate Variability) tracking',
                            '4 Video Consultations with Visphy Kharradi',
                            'Custom adrenal adaptogen protocol',
                            'Workplace stress boundary strategy',
                            'Priority WhatsApp support'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'Bridal & Red Carpet Transformation',
                'slug' => 'bridal-red-carpet-transformation',
                'short_description' => 'High-impact signature transformation program designed to achieve glowing skin, peak body tone, and confidence for your big day.',
                'full_description' => '<p>Look and feel breathtaking on your wedding day or special event. The <strong>Bridal & Red Carpet Transformation</strong> delivers targeted sculpting, radiant skin nutrition, and rapid anti-bloat strategies.</p><h3>What makes it special:</h3><ul><li>Skin radiance & hair health micronutrient stack</li><li>Targeted inch loss & waist sculpting</li><li>Fitting date checkpoint planning</li><li>Wedding week anti-bloat peak strategy</li></ul>',
                'badge' => 'popular',
                'selling_price' => 48000.00,
                'regular_price' => 60000.00,
                'duration_value' => 2,
                'duration_unit' => 'month',
                'featured_image' => 'uploads/packages/bridal-transformation.jpg',
                'is_active' => 1,
                'is_featured' => 1,
                'display_order' => 100,
                'cta_label' => 'Book Bridal Coaching',
                'google_form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSc-bridal/viewform',
                'whatsapp_template' => 'Congratulations {customer_name}! Let us prepare you to shine on your big day.',
                'package_features' => [
                    'Express Waist Sculpting & Body Toning',
                    'Skin Glow & Anti-Inflammatory Nutrition',
                    'Dress Fitting Checkpoint Reviews',
                    'Peak Week Wedding Anti-Bloat Strategy',
                ],
                'options' => [
                    [
                        'name' => '2 Month Express Bridal Glow',
                        'duration_value' => 2,
                        'duration_unit' => 'month',
                        'price' => 48000.00,
                        'short_description' => 'Rapid body toning and radiant skin prep for upcoming weddings or events.',
                        'sort_order' => 1,
                        'is_active' => 1,
                        'features' => [
                            'Targeted inch loss & arm/waist sculpt routine',
                            '3 Video Calls with Visphy Kharradi',
                            'Dermatologist-approved skin glow meal plan',
                            'Pre-wedding week anti-bloat secret strategy',
                            'WhatsApp support'
                        ]
                    ],
                    [
                        'name' => '4 Month Signature Bridal Makeover',
                        'duration_value' => 4,
                        'duration_unit' => 'month',
                        'price' => 85000.00,
                        'short_description' => 'The ultimate bride transformation: flawless body sculpting, hair & skin vitality, and lasting wellness habits.',
                        'sort_order' => 2,
                        'is_active' => 1,
                        'features' => [
                            'Complete signature body transformation & tone',
                            '6 Video Calls with Visphy Kharradi',
                            'Hair, nail & collagen boost nutrition stack',
                            'Groom/Partner meal prep bonus guide',
                            'Priority VIP WhatsApp support'
                        ]
                    ]
                ]
            ]
        ];

        foreach ($packages as $pData) {
            $pFeatures = $pData['package_features'];
            $pOptions = $pData['options'];
            unset($pData['package_features'], $pData['options']);

            $pData['created_at'] = $now;
            $pData['updated_at'] = $now;

            $pkgId = $packageModel->insert($pData, true);
            CLI::write("Inserted package [ID: {$pkgId}]: {$pData['name']}", 'green');

            // Insert general features
            foreach ($pFeatures as $idx => $fText) {
                $featureModel->insert([
                    'package_id' => $pkgId,
                    'feature_text' => $fText,
                    'sort_order' => $idx + 1,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Insert options and option features
            foreach ($pOptions as $optData) {
                $oFeatures = $optData['features'];
                unset($optData['features']);

                $optData['package_id'] = $pkgId;
                $optData['created_at'] = $now;
                $optData['updated_at'] = $now;

                $optId = $optionModel->insert($optData, true);
                CLI::write("  -> Option [ID: {$optId}]: {$optData['name']} (₹{$optData['price']})", 'cyan');

                foreach ($oFeatures as $idx => $fText) {
                    $optionFeatureModel->insert([
                        'package_option_id' => $optId,
                        'feature_text' => $fText,
                        'sort_order' => $idx + 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        CLI::write("SUCCESSFULLY SEEDED 10 REAL PACKAGES!", 'green');
    }
}
