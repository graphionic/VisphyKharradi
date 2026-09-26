/* ==========================================================================
   FTPRENEUR — Program Experience · Concept 02 · Visual Program Editions
   PROTOTYPE / VISUALISATION DATA — not production database content.

   Image convention:  assets/programs/program-NN-<slug-word>.webp
   To replace a photo, drop a new file in with the same name (no redesign).
   If a file is missing, the card and drawer show a labelled image slot.
   imageFocus = CSS object-position, used to control the crop per photo.
   disciplines[].weight = 1 support · 2 core · 3 lead (focus visual only).
   ========================================================================== */
(function () {
  var D = function (name, weight) { return { name: name, weight: weight }; };

  window.ED_JOURNEY_NOTES = {
    Assess:      'A 1:1 look at your history, routine, food habits and goals.',
    Understand:  'We start with your health needs, routine and food preferences.',
    Personalise: 'Your plan is built around your life, not copied from a template.',
    Structure:   'Food, movement and routine organised into a plan you can follow.',
    Learn:       'Technique first: the movements you will build on, coached properly.',
    Move:        'Daily movement built into the day you already have.',
    Build:       'Steady weeks of structure across food, training and habits.',
    Apply:       'Guidance put into practice across real meals and real weeks.',
    Adapt:       'The plan is adjusted as your routine, energy and progress change.',
    Review:      'Regular check-ins to see what is working and refine the rest.',
    Progress:    'Next steps, and a clear plan to keep moving forward.',
    Sustain:     'Habits settled into a routine that holds after the program.',
    Maintain:    'A routine designed to keep going well beyond the program.'
  };

  window.ED_PROGRAMS = [
    {
      id: 1, number: '01', slug: 'sustainable-weight-reset',
      name: 'Sustainable Weight Reset', shortName: 'Weight reset', category: 'Weight',
      image: 'assets/programs/program-01-weight.webp', imageFocus: '50% 40%',
      imageAlt: 'Woman walking up outdoor park steps in the early morning',
      durationWeeks: 12, price: 14999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Build sustainable progress',
      summary: 'A personalised approach to nutrition, movement and everyday habits designed to support sustainable weight-management progress.',
      suitableFor: 'People looking for a structured, realistic approach to weight management without crash-diet thinking.',
      disciplines: [D('Nutrition', 3), D('Strength', 2), D('Lifestyle', 3)],
      focusAreas: ['Food structure', 'Movement', 'Routine', 'Consistency'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Personalised plan', 'Nutrition guidance', 'Strength guidance', 'Progress reviews', 'Lifestyle framework', 'Support & follow-up'],
      journey: ['Assess', 'Personalise', 'Build', 'Review', 'Progress']
    },
    {
      id: 2, number: '02', slug: 'metabolic-health-program',
      name: 'Metabolic Health Program', shortName: 'Metabolic health', category: 'Metabolic',
      image: 'assets/programs/program-02-metabolic.webp', imageFocus: '42% 35%',
      imageAlt: 'Man preparing a home-cooked meal with fresh vegetables in a bright kitchen',
      durationWeeks: 12, price: 17999, discountPrice: null,
      featured: true, featuredLabel: 'Signature program',
      eyebrow: 'Better daily patterns',
      summary: 'Food, movement and routine structured around the individual to support better metabolic health and long-term wellbeing.',
      suitableFor: 'People who want structured lifestyle support around metabolic health, daily habits, movement and nutrition.',
      disciplines: [D('Nutrition', 3), D('Strength', 2), D('Lifestyle', 3)],
      focusAreas: ['Nutrition patterns', 'Daily movement', 'Strength', 'Sleep & routine', 'Consistency'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['1:1 assessment', 'Personalised nutrition structure', 'Movement guidance', 'Strength guidance', 'Lifestyle framework', 'Progress reviews', 'Support & follow-up'],
      journey: ['Understand', 'Assess', 'Structure', 'Adapt', 'Progress'],
      healthNote: 'Designed to work alongside your doctor\u2019s care. It does not replace medical advice, treatment or medication.'
    },
    {
      id: 3, number: '03', slug: 'strength-foundation',
      name: 'Strength Foundation', shortName: 'Strength foundation', category: 'Strength',
      image: 'assets/programs/program-03-strength.webp', imageFocus: '50% 30%',
      imageAlt: 'Woman performing a controlled kettlebell squat in a training studio',
      durationWeeks: 8, price: 9999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Build capability',
      summary: 'A progressive strength-focused program designed around ability, movement quality and sustainable progression.',
      suitableFor: 'People who want to become stronger and more confident with structured training.',
      disciplines: [D('Strength', 3), D('Mobility', 2), D('Lifestyle', 1)],
      focusAreas: ['Technique', 'Strength', 'Mobility', 'Recovery'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Strength assessment', 'Personalised training structure', 'Movement guidance', 'Progress reviews', 'Recovery guidance', 'Support'],
      journey: ['Assess', 'Learn', 'Build', 'Progress', 'Review']
    },
    {
      id: 4, number: '04', slug: 'nutrition-reset',
      name: 'Nutrition Reset', shortName: 'Nutrition reset', category: 'Nutrition',
      image: 'assets/programs/program-04-nutrition.webp', imageFocus: '50% 50%',
      imageAlt: 'Balanced home-cooked Indian meal arranged in ceramic bowls',
      durationWeeks: 8, price: 8999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Food that fits real life',
      summary: 'Personalised nutrition guidance built around food preferences, routine and realistic long-term habits.',
      suitableFor: 'People who want more structure and consistency around everyday eating.',
      disciplines: [D('Nutrition', 3), D('Lifestyle', 2)],
      focusAreas: ['Meal structure', 'Food choices', 'Routine', 'Consistency'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Nutrition assessment', 'Personalised food structure', 'Meal guidance', 'Habit framework', 'Progress reviews', 'Support'],
      journey: ['Understand', 'Structure', 'Apply', 'Review', 'Sustain']
    },
    {
      id: 5, number: '05', slug: 'active-lifestyle',
      name: 'Active Lifestyle', shortName: 'Active lifestyle', category: 'Lifestyle',
      image: 'assets/programs/program-05-lifestyle.webp', imageFocus: '55% 45%',
      imageAlt: 'Man cycling on a quiet tree-lined road at golden hour',
      durationWeeks: 8, price: 9999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Move better. Live better.',
      summary: 'A practical movement and lifestyle structure designed to make activity part of everyday life.',
      suitableFor: 'People looking to build a more active and consistent everyday routine.',
      disciplines: [D('Movement', 3), D('Strength', 2), D('Lifestyle', 3)],
      focusAreas: ['Daily movement', 'Mobility', 'Strength', 'Routine'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Lifestyle assessment', 'Movement structure', 'Strength guidance', 'Mobility guidance', 'Routine framework', 'Progress reviews'],
      journey: ['Assess', 'Move', 'Build', 'Adapt', 'Maintain']
    },
    {
      id: 6, number: '06', slug: 'complete-transformation',
      name: 'Complete Transformation', shortName: 'Complete transformation', category: 'Complete',
      image: 'assets/programs/program-06-complete.webp', imageFocus: '40% 60%',
      imageAlt: 'Woman holding a strong plank position in a bright studio',
      durationWeeks: 16, price: 24999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'The complete approach',
      summary: 'An integrated nutrition, strength and lifestyle program built around the individual and designed for sustained progress.',
      suitableFor: 'People looking for comprehensive personalised support across nutrition, training and everyday lifestyle.',
      disciplines: [D('Nutrition', 3), D('Strength', 3), D('Lifestyle', 3)],
      focusAreas: ['Nutrition', 'Strength', 'Movement', 'Routine', 'Recovery', 'Consistency'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['1:1 assessment', 'Personalised nutrition plan', 'Personalised training plan', 'Lifestyle framework', 'Progress reviews', 'Ongoing support'],
      journey: ['Assess', 'Personalise', 'Build', 'Adapt', 'Progress', 'Sustain']
    },

    /* ---- DEMO RECORDS 07–12 (show-more demonstration only) ---- */
    {
      id: 7, number: '07', slug: 'mobility-and-strength',
      name: 'Mobility & Strength', shortName: 'Mobility & strength', category: 'Strength',
      image: 'assets/programs/program-07-mobility.webp', imageFocus: '50% 40%',
      imageAlt: 'Man doing a deep lunge-and-reach mobility stretch on a studio floor',
      durationWeeks: 10, price: 11999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Move with control',
      summary: 'Mobility and strength work combined, so everyday movement feels easier and training feels more capable.',
      suitableFor: 'People who feel stiff or restricted and want to build strength through better movement.',
      disciplines: [D('Mobility', 3), D('Strength', 3), D('Lifestyle', 1)],
      focusAreas: ['Joint mobility', 'Control', 'Strength', 'Recovery'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Movement assessment', 'Mobility routine', 'Strength structure', 'Recovery guidance', 'Progress reviews', 'Support'],
      journey: ['Assess', 'Learn', 'Build', 'Adapt', 'Progress']
    },
    {
      id: 8, number: '08', slug: 'healthy-routine-reset',
      name: 'Healthy Routine Reset', shortName: 'Healthy routine reset', category: 'Lifestyle',
      image: 'assets/programs/program-08-routine.webp', imageFocus: '50% 40%',
      imageAlt: 'Woman sitting by a bright window in the morning with a glass of water',
      durationWeeks: 6, price: 6999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Small daily structure',
      summary: 'Sleep, meals, movement and daily rhythm reorganised into a routine that is easier to keep.',
      suitableFor: 'People whose days feel unstructured and who want calmer, more consistent daily habits.',
      disciplines: [D('Lifestyle', 3), D('Nutrition', 2), D('Movement', 1)],
      focusAreas: ['Sleep & routine', 'Meal timing', 'Daily movement', 'Stress habits'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Routine assessment', 'Daily structure plan', 'Meal timing guidance', 'Habit framework', 'Progress reviews', 'Support'],
      journey: ['Understand', 'Structure', 'Apply', 'Review', 'Sustain']
    },
    {
      id: 9, number: '09', slug: 'performance-nutrition',
      name: 'Performance Nutrition', shortName: 'Performance nutrition', category: 'Nutrition',
      image: 'assets/programs/program-09-performance.webp', imageFocus: '50% 45%',
      imageAlt: 'Runner eating a simple meal on track-side steps after training',
      durationWeeks: 10, price: 12999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Fuel your training',
      summary: 'Nutrition structured around your training load, schedule and recovery, built on everyday food.',
      suitableFor: 'Active people and recreational athletes who want food to support their training.',
      disciplines: [D('Nutrition', 3), D('Strength', 2), D('Lifestyle', 1)],
      focusAreas: ['Training fuel', 'Recovery meals', 'Hydration', 'Consistency'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Nutrition assessment', 'Training-day food structure', 'Recovery guidance', 'Hydration framework', 'Progress reviews', 'Support'],
      journey: ['Assess', 'Structure', 'Apply', 'Adapt', 'Progress']
    },
    {
      id: 10, number: '10', slug: 'long-term-wellness',
      name: 'Long-term Wellness', shortName: 'Long-term wellness', category: 'Metabolic',
      image: 'assets/programs/program-10-wellness.webp', imageFocus: '62% 60%',
      imageAlt: 'Couple walking together along a seaside promenade in the morning',
      durationWeeks: 24, price: 29999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Health habits for the long run',
      summary: 'A longer, steadier program that works around individual health needs to support healthier daily patterns and long-term wellbeing.',
      suitableFor: 'People who want ongoing personalised lifestyle support over a longer period.',
      disciplines: [D('Nutrition', 3), D('Movement', 2), D('Lifestyle', 3)],
      focusAreas: ['Nutrition patterns', 'Daily movement', 'Sleep & routine', 'Long-term habits'],
      format: '1:1 personalised', reviewFrequency: 'Every 3 weeks',
      included: ['1:1 assessment', 'Personalised nutrition structure', 'Movement guidance', 'Lifestyle framework', 'Progress reviews', 'Ongoing support'],
      journey: ['Understand', 'Personalise', 'Build', 'Adapt', 'Sustain'],
      healthNote: 'Designed to work alongside your doctor\u2019s care. It does not replace medical advice, treatment or medication.'
    },
    {
      id: 11, number: '11', slug: 'body-composition',
      name: 'Body Composition', shortName: 'Body composition', category: 'Weight',
      image: 'assets/programs/program-11-composition.webp', imageFocus: '50% 40%',
      imageAlt: 'Program image: body-composition training (image slot)',
      durationWeeks: 12, price: 15999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Strength meets structure',
      summary: 'Training and nutrition combined to support a healthier body composition at a sustainable pace.',
      suitableFor: 'People who want to build strength while working towards a healthier body composition.',
      disciplines: [D('Strength', 3), D('Nutrition', 3), D('Lifestyle', 1)],
      focusAreas: ['Strength', 'Food structure', 'Recovery', 'Consistency'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['1:1 assessment', 'Training structure', 'Nutrition structure', 'Recovery guidance', 'Progress reviews', 'Support'],
      journey: ['Assess', 'Personalise', 'Build', 'Review', 'Progress']
    },
    {
      id: 12, number: '12', slug: 'strength-plus-nutrition',
      name: 'Strength + Nutrition', shortName: 'Strength + nutrition', category: 'Strength',
      image: 'assets/programs/program-12-strength-nutrition.webp', imageFocus: '50% 40%',
      imageAlt: 'Program image: strength and nutrition coaching (image slot)',
      durationWeeks: 12, price: 16999, discountPrice: null,
      featured: false, featuredLabel: null,
      eyebrow: 'Train it. Fuel it.',
      summary: 'A paired strength and nutrition program, where training and food are planned to work together.',
      suitableFor: 'People who train, or want to, and want their eating to support it properly.',
      disciplines: [D('Strength', 3), D('Nutrition', 3), D('Lifestyle', 2)],
      focusAreas: ['Progressive training', 'Meal structure', 'Recovery', 'Routine'],
      format: '1:1 personalised', reviewFrequency: 'Every 2 weeks',
      included: ['Strength assessment', 'Personalised training plan', 'Personalised nutrition plan', 'Recovery guidance', 'Progress reviews', 'Support'],
      journey: ['Assess', 'Learn', 'Build', 'Adapt', 'Progress']
    }
  ];
})();
