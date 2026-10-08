<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * PackageSeeder — Seeds real client FTPRENEUR offerings:
 * 1. Visphy Kharradi Personal Guidance Programme (3M / 6M)
 * 2. Visphy-Designed Team-Guided Programme (3M / 6M)
 * 3. One-to-One Counselling with Visphy Kharradi (Standalone ₹15k)
 *
 * Deactivates demo packages (soft/is_active = 0) without destroying historical references.
 * ZERO DATABASE FK CONSTRAINTS — logical references only.
 */
class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $realSlugs = [
            'visphy-kharradi-personal-guidance-programme',
            'visphy-designed-team-guided-programme',
            'one-to-one-counselling-with-visphy-kharradi',
        ];

        // Deactivate demo/legacy packages so only the 3 real client offerings are active
        $this->db->table('packages')
            ->whereNotIn('slug', $realSlugs)
            ->update([
                'is_active'  => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        $programmes = [
            [
                'name'              => 'Visphy Kharradi Personal Guidance Programme',
                'slug'              => 'visphy-kharradi-personal-guidance-programme',
                'short_description' => 'Personalised nutrition and strength/exercise strategy designed by Visphy Kharradi, with direct 1-to-1 video counselling sessions.',
                'full_description'  => '<p>For individuals who want their complete personalised plan designed by Visphy Kharradi, together with direct personal guidance from Visphy.</p><p>Visphy personally studies the case, designs the nutrition and exercise strategy, reviews important progress updates and conducts two one-to-one video counselling sessions during the programme.</p>',
                'regular_price'     => '50000.00',
                'selling_price'     => '50000.00',
                'duration_value'    => 3,
                'duration_unit'     => 'months',
                'badge'             => 'Personal Guidance',
                'cta_label'         => 'Enrol Now',
                'display_order'     => 1,
                'is_featured'       => 1,
                'is_active'         => 1,
                'options'           => [
                    [
                        'name'              => '3 Months',
                        'duration_value'    => 3,
                        'duration_unit'     => 'month',
                        'price'             => '50000.00',
                        'short_description' => '3 Months Personal Guidance by Visphy Kharradi',
                        'sort_order'        => 1,
                        'is_active'         => 1,
                    ],
                    [
                        'name'              => '6 Months',
                        'duration_value'    => 6,
                        'duration_unit'     => 'month',
                        'price'             => '80000.00',
                        'short_description' => '6 Months Personal Guidance by Visphy Kharradi',
                        'sort_order'        => 2,
                        'is_active'         => 1,
                    ],
                ],
                'features'          => [
                    'Detailed evaluation of lifestyle, health conditions, medical history, goals and relevant blood reports',
                    'Personalised nutrition plan designed by Visphy Kharradi',
                    'Personalised strength-training and exercise plan designed by Visphy Kharradi',
                    'Two private video-call counselling sessions with Visphy Kharradi (approx. 30 mins each)',
                    'Discuss progress, challenges, doubts and lifestyle directly with Visphy',
                    'Regular progress monitoring by the Visphy Kharradi team',
                    'Nutrition and exercise-plan modifications based on progress, reports and medical condition',
                    'Food alternatives based on preferences, routine, travel and availability',
                    'Exercise alternatives according to fitness level, mobility, equipment and medical limitations',
                    'Periodic reminders for relevant progress reports and blood tests',
                    'Priority coordination and support from the Visphy Kharradi team',
                    'Access to relevant client webinars, educational sessions and wellness challenges whenever scheduled',
                ],
            ],
            [
                'name'              => 'Visphy-Designed Team-Guided Programme',
                'slug'              => 'visphy-designed-team-guided-programme',
                'short_description' => 'Personalised plan created by Visphy Kharradi with regular guidance, counselling and follow-up support from his trained team.',
                'full_description'  => '<p>For individuals who want a personalised plan created by Visphy Kharradi while receiving regular guidance, counselling and follow-up support from his trained team.</p><p>Routine consultations, counselling, questions, monitoring, follow-ups and plan coordination are handled by the assigned team member. Direct 1-to-1 video counselling with Visphy Kharradi is not included.</p>',
                'regular_price'     => '25000.00',
                'selling_price'     => '25000.00',
                'duration_value'    => 3,
                'duration_unit'     => 'months',
                'badge'             => 'Team-Guided',
                'cta_label'         => 'Enrol Now',
                'display_order'     => 2,
                'is_featured'       => 0,
                'is_active'         => 1,
                'options'           => [
                    [
                        'name'              => '3 Months',
                        'duration_value'    => 3,
                        'duration_unit'     => 'month',
                        'price'             => '25000.00',
                        'short_description' => '3 Months Team-Guided Programme',
                        'sort_order'        => 1,
                        'is_active'         => 1,
                    ],
                    [
                        'name'              => '6 Months',
                        'duration_value'    => 6,
                        'duration_unit'     => 'month',
                        'price'             => '40000.00',
                        'short_description' => '6 Months Team-Guided Programme',
                        'sort_order'        => 2,
                        'is_active'         => 1,
                    ],
                ],
                'features'          => [
                    'Detailed evaluation of lifestyle, health conditions, medical history, goals and relevant blood reports',
                    'Personalised nutrition plan designed by Visphy Kharradi',
                    'Personalised strength-training and exercise plan designed by Visphy Kharradi',
                    'Assigned member of the Visphy Kharradi team throughout the programme',
                    'Scheduled consultation and counselling with the assigned team member',
                    'Regular WhatsApp check-ins and progress follow-ups',
                    'Nutrition and exercise-plan modifications based on progress, reports and changing requirements',
                    'Food alternatives based on preferences, schedule and availability',
                    'Exercise alternatives according to fitness level, mobility and available equipment',
                    'Periodic reminders for relevant progress reports and blood tests',
                    'Access to relevant client webinars, educational sessions and wellness challenges whenever scheduled',
                ],
            ],
            [
                'name'              => 'One-to-One Counselling with Visphy Kharradi',
                'slug'              => 'one-to-one-counselling-with-visphy-kharradi',
                'short_description' => 'Focused 30-minute private video consultation with Visphy Kharradi for personalised lifestyle, fitness and motivational guidance.',
                'full_description'  => '<p>A focused one-to-one private video consultation for individuals who want to speak directly with Visphy Kharradi without enrolling in a complete three-month or six-month programme.</p><p>This standalone consultation includes one private video session for personalised discussion and practical guidance. It does not include written charts, blood report evaluation, or ongoing WhatsApp support.</p>',
                'regular_price'     => '15000.00',
                'selling_price'     => '15000.00',
                'duration_value'    => 30,
                'duration_unit'     => 'minutes',
                'badge'             => 'Consultation',
                'cta_label'         => 'Book Session',
                'display_order'     => 3,
                'is_featured'       => 0,
                'is_active'         => 1,
                'options'           => [],
                'features'          => [
                    'One private video consultation with Visphy Kharradi (approx. 30 minutes)',
                    'Personalised discussion based on individual concerns and health goals',
                    'Practical lifestyle, fitness and motivational guidance',
                    'Direct opportunity to ask questions regarding nutrition, exercise and routine',
                    'Guidance on disease-management, weight challenges, and discipline',
                    'Does NOT include written nutrition chart or exercise programme',
                    'Does NOT include blood-report evaluation or ongoing WhatsApp support',
                ],
            ],
        ];

        foreach ($programmes as $prog) {
            $options = $prog['options'] ?? [];
            $features = $prog['features'] ?? [];
            unset($prog['options'], $prog['features']);

            $existing = $this->db->table('packages')->where('slug', $prog['slug'])->get()->getRowArray();
            if ($existing) {
                $packageId = (int) $existing['id'];
                $this->db->table('packages')->where('id', $packageId)->update(array_merge($prog, [
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            } else {
                $prog['created_at'] = date('Y-m-d H:i:s');
                $prog['updated_at'] = date('Y-m-d H:i:s');
                $this->db->table('packages')->insert($prog);
                $packageId = (int) $this->db->insertID();
            }

            // Sync features idempotently
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

            // Sync options idempotently
            $this->db->table('package_options')->where('package_id', $packageId)->delete();
            foreach ($options as $opt) {
                $this->db->table('package_options')->insert([
                    'package_id'        => $packageId,
                    'name'              => $opt['name'],
                    'duration_value'    => $opt['duration_value'],
                    'duration_unit'     => $opt['duration_unit'],
                    'price'             => $opt['price'],
                    'short_description' => $opt['short_description'] ?? null,
                    'sort_order'        => $opt['sort_order'] ?? 1,
                    'is_active'         => $opt['is_active'] ?? 1,
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);
            }
        }

        echo "PackageSeeder: 3 real client programmes successfully seeded.\n";
    }
}
