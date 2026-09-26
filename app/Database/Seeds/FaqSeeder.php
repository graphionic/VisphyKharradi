<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * FaqSeeder — Phase 06: Dynamic FAQ Seed Data
 *
 * Seeds initial responsible, high-value FAQs.
 * Idempotent: checks by question text so existing records are preserved.
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question'      => 'Who is Ftpreneur for?',
                'answer'        => 'Ftpreneur is built for ambitious founders, executives, working professionals, and individuals who want structured, scientific guidance across nutrition, strength training, and lifestyle habits. Every plan is adapted to your unique daily schedule and health baseline.',
                'category'      => 'General',
                'display_order' => 10,
                'is_active'     => 1,
            ],
            [
                'question'      => 'Are the programs personalized?',
                'answer'        => 'Yes, 100%. We do not use template meal plans or generic workout routines. Your program is custom-crafted around your specific physiological baseline, movement capacity, food preferences, work travel, and daily schedule.',
                'category'      => 'Program Structure',
                'display_order' => 20,
                'is_active'     => 1,
            ],
            [
                'question'      => 'How does the initial assessment work?',
                'answer'        => 'After selecting your program, you will complete a comprehensive 1:1 onboarding assessment covering your health history, dietary patterns, sleep routine, stress factors, and fitness experience. This objective data forms the foundation of your plan.',
                'category'      => 'Onboarding',
                'display_order' => 30,
                'is_active'     => 1,
            ],
            [
                'question'      => 'Do I need access to a gym?',
                'answer'        => 'No. Training programs are tailored specifically to your environment—whether you train in a commercial gym, a minimalist home setup, or rely strictly on bodyweight and travel routines.',
                'category'      => 'Training',
                'display_order' => 40,
                'is_active'     => 1,
            ],
            [
                'question'      => 'What is the nutrition approach?',
                'answer'        => 'We prioritize flexible, sustainable nutrition built around real-world food choices. Rather than restrictive crash dieting, we structure macronutrient balance and eating routines that integrate naturally with your family and social life.',
                'category'      => 'Nutrition',
                'display_order' => 50,
                'is_active'     => 1,
            ],
            [
                'question'      => 'How is my progress reviewed and monitored?',
                'answer'        => 'You receive ongoing 1:1 coaching with structured biweekly check-ins. We evaluate metrics such as energy levels, strength markers, body composition, and routine adherence to make data-driven refinements as you progress.',
                'category'      => 'Coaching & Support',
                'display_order' => 60,
                'is_active'     => 1,
            ],
            [
                'question'      => 'Can Ftpreneur help with lifestyle health management?',
                'answer'        => 'Yes. We incorporate evidence-based lifestyle interventions focusing on metabolic health, stress optimization, sleep architecture, and movement to support better daily energy, vitality, and long-term health management.',
                'category'      => 'Health Management',
                'display_order' => 70,
                'is_active'     => 1,
            ],
            [
                'question'      => 'What happens after I select my program?',
                'answer'        => 'Once you select a program, you will receive immediate access to your 1:1 onboarding questionnaire and baseline intake process, followed by direct communication from Visphy Kharradi to begin your tailored strategy.',
                'category'      => 'Next Steps',
                'display_order' => 80,
                'is_active'     => 1,
            ],
        ];

        $now = date('Y-m-d H:i:s');
        $insertedCount = 0;
        $updatedCount = 0;

        foreach ($faqs as $item) {
            $existing = $this->db->table('faqs')
                ->where('question', $item['question'])
                ->where('deleted_at IS NULL', null, false)
                ->get()
                ->getRowArray();

            if ($existing) {
                $this->db->table('faqs')->where('id', $existing['id'])->update([
                    'answer'        => $item['answer'],
                    'category'      => $item['category'],
                    'display_order' => $item['display_order'],
                    'updated_at'    => $now,
                ]);
                $updatedCount++;
            } else {
                $this->db->table('faqs')->insert([
                    'question'      => $item['question'],
                    'answer'        => $item['answer'],
                    'category'      => $item['category'],
                    'display_order' => $item['display_order'],
                    'is_active'     => $item['is_active'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $insertedCount++;
            }
        }

        echo "FaqSeeder: Processed " . count($faqs) . " items ({$insertedCount} inserted, {$updatedCount} updated).\n";
    }
}
