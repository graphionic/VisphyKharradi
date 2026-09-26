/* ==========================================================================
   FTPRENEUR — Client Results · shared prototype data (carousel + drawer)
   Mirrors the future Client Result record: identity, program, focus,
   quote (short testimonial), story (full_story), results (public metrics,
   display order) and optional evidence. Missing evidence = section hidden.

   PROTOTYPE NOTES
   - Records 01–04: supplied demo client results. The `story` paragraphs are
     prototype copy written for layout review (Phase 09 renders full_story).
   - Records 05–08: PLACEHOLDER records for layout only.
   - `evidence` on Aarav (full set) and Meera (report only) is a DEMO of the
     component. The media are neutral placeholders, not client material.
   ========================================================================== */
window.CRJ_STORIES = [
  {
    name: 'Aarav M.', initials: 'AM', context: 'Business Owner, 42', program: 'Personalised Health Coaching', weeks: 16, featured: true,
    focus: ['Weight Loss', 'Metabolic Health', 'Diabetic Control', 'Energy'],
    quote: 'I finally had a plan that worked around my lifestyle instead of forcing my lifestyle around the plan.',
    results: [
      { label: 'Weight', before: '92', after: '78', unit: 'kg', beforeDate: '12 Jan 2026', afterDate: '04 May 2026' },
      { label: 'HbA1c', before: '8.2', after: '6.4', unit: '%', beforeDate: '10 Jan 2026', afterDate: '02 May 2026' },
      { label: 'Waist', before: '42', after: '36', unit: 'in', beforeDate: '12 Jan 2026', afterDate: '04 May 2026' }
    ],
    story: [
      'When Aarav started, his days were built around the business: early calls, late dinners and meals eaten wherever the day allowed. He had tried structured diets before, but each one assumed a routine he simply did not have.',
      'The hardest part was consistency. Travel weeks and client dinners would undo a good fortnight, and every restart felt like starting from zero. He also wanted his plan to sit alongside the guidance he was already receiving from his doctor.',
      'His plan was built around the week he actually lived. Meals were organised around a few fixed anchors he could keep even on busy days, movement was scheduled into existing gaps rather than added on top, and each check-in adjusted the plan whenever work changed shape.',
      'Over sixteen weeks the changes were gradual and recorded at each review. What stayed with him most was not a single number, but knowing how to adapt the plan himself when a week did not go to plan.'
    ],
    recorded: 'Recorded across the 16-week journey',
    evidence: {
      beforeAfter: { before: { caption: 'Week 0', date: '12 Jan 2026' }, after: { caption: 'Week 16', date: '04 May 2026' } },
      progress: [{ caption: 'Week 4' }, { caption: 'Week 8' }, { caption: 'Week 12' }],
      lifestyle: [
        { caption: 'A weekday lunch planned around client meetings', shape: 'tall' },
        { caption: 'A short morning walk before the first call', shape: 'wide' },
        { caption: 'Sunday preparation for the week ahead', shape: 'square' }
      ],
      reports: [
        { type: 'pdf', title: 'Lab report', kind: 'Assessment report', date: '10 Jan 2026', pages: 2 },
        { type: 'image', title: 'Body composition', kind: 'Progress assessment', date: '18 Mar 2026' },
        { type: 'pdf', title: 'Lab report', kind: 'Progress report', date: '02 May 2026', pages: 2 }
      ]
    }
  },
  {
    name: 'Meera S.', initials: 'MS', context: 'Marketing Professional, 36', program: 'Metabolic Health & Reversal', weeks: 20, featured: true,
    image: 'assets/sample/client-photo-sample.jpg', imageSample: true,
    focus: ['Cholesterol', 'Metabolic Health', 'Weight Management', 'Lifestyle'],
    quote: 'The biggest change was understanding what I could actually sustain instead of repeatedly starting over.',
    results: [
      { label: 'Weight', before: '84', after: '73', unit: 'kg' },
      { label: 'Total Cholesterol', before: '245', after: '188', unit: 'mg/dL' },
      { label: 'Waist', before: '39', after: '34', unit: 'in' }
    ],
    story: [
      'Meera had started over more times than she could count. Each attempt began with a strict plan and ended a few weeks later, usually when work got busy or a family event came up.',
      'Rather than another restart, her program began with understanding her routine, her food preferences and the habits she could realistically keep. Changes were introduced a few at a time, and each review looked at what was working before adding anything new.',
      'Over twenty weeks her plan became something she could sustain rather than survive. She describes the biggest shift as learning what to do on the imperfect weeks, not just the good ones.'
    ],
    recorded: 'Recorded across the 20-week journey',
    evidence: {
      reports: [{ type: 'pdf', title: 'Lipid profile', kind: 'Progress report', date: '22 Apr 2026', pages: 1 }]
    }
  },
  {
    name: 'Vikram D.', initials: 'VD', context: 'Finance Professional, 48', program: 'Personalised Health Coaching', weeks: 24, featured: true,
    focus: ['Weight Loss', 'Blood Pressure', 'Strength', 'Sustainable Habits'],
    quote: 'The process was gradual, practical and measurable. That made it much easier for me to stay consistent.',
    results: [
      { label: 'Weight', before: '101', after: '86', unit: 'kg' },
      { label: 'Waist', before: '44', after: '38', unit: 'in' },
      { label: 'Blood Pressure', before: '148/94', after: '128/82', unit: 'mmHg' }
    ],
    story: [
      'Vikram came in wanting a structured approach that respected a demanding schedule and a long commute. He wanted to see progress in a way he could track, not just feel.',
      'His plan combined steady strength work with a practical food structure and small routine changes around sleep and workdays. Measurements were recorded at regular reviews, alongside the care he was already receiving.',
      'Across twenty-four weeks progress was gradual rather than dramatic. For Vikram that was the point: a pace he could keep, with changes he could see recorded along the way.'
    ],
    recorded: 'Recorded across the 24-week journey'
  },
  {
    name: 'Rohan K.', initials: 'RK', context: 'Technology Consultant, 39', program: 'Executive Performance Protocol', weeks: 12,
    focus: [],
    quote: 'The structure gave me clarity. I knew what to focus on each week without turning fitness into another full-time job.',
    results: [
      { label: 'Body Weight', before: '88', after: '82', unit: 'kg' },
      { label: 'Waist', before: '38', after: '34.5', unit: 'in' },
      { label: 'Deadlift', before: '90', after: '130', unit: 'kg' }
    ],
    story: [
      'Rohan\u2019s work already demanded most of his attention. He wanted a clear plan that would fit around it rather than become another project to manage.',
      'Each week came with a small number of clear priorities across training, food and recovery. Strength sessions progressed steadily, and the plan was adjusted around travel and heavy delivery weeks.',
      'Over twelve weeks the structure did the heavy lifting. He knew what mattered that week and could let go of the rest.'
    ],
    recorded: 'Recorded across the 12-week journey'
  },

  /* ---- PLACEHOLDER records (layout only). Replace with the real Client Result records. ---- */
  { placeholder: true, name: 'Nisha P.', initials: 'NP', context: 'Product Designer, 33', program: 'Nutrition Reset', weeks: 12,
    focus: ['Nutrition', 'Energy', 'Routine'],
    quote: 'Meals finally fit my workday. I stopped feeling like every week needed a fresh start.',
    results: [{ label: 'Weight', before: '71', after: '65', unit: 'kg' }, { label: 'Waist', before: '34', after: '31', unit: 'in' }],
    story: ['Placeholder story for layout review. Long workdays meant meals were often skipped or rushed, and plans rarely survived a busy week.', 'Her plan focused on simple meal structure around the working day, with small adjustments at each review.'],
    recorded: 'Recorded across the 12-week journey' },
  { placeholder: true, name: 'Isha R.', initials: 'IR', context: 'Architect, 29', program: 'Strength Foundation', weeks: 10,
    focus: ['Strength', 'Mobility'],
    quote: 'I learned to train properly, and feeling stronger changed how I approach the rest of my day.',
    results: [{ label: 'Squat', before: '30', after: '55', unit: 'kg' }, { label: 'Push-ups', before: '4', after: '15', unit: 'reps' }],
    story: ['Placeholder story for layout review. Isha wanted to start strength training but did not know where to begin.', 'Sessions started with technique and progressed steadily, with mobility work built in from the first week.'],
    recorded: 'Recorded across the 10-week journey' },
  { placeholder: true, name: 'Kabir T.', initials: 'KT', context: 'Chartered Accountant, 45', program: 'Personalised Health Coaching', weeks: 16,
    focus: ['Weight Loss', 'Lifestyle', 'Strength'],
    quote: 'Small, steady changes I could keep up with, even during the busiest months of the year.',
    results: [{ label: 'Weight', before: '96', after: '86', unit: 'kg' }, { label: 'Waist', before: '41', after: '37', unit: 'in' }],
    story: ['Placeholder story for layout review. Filing season meant long hours and irregular meals every year.', 'His plan was designed to hold up in the busiest months, with a lighter version ready for the hardest weeks.'],
    recorded: 'Recorded across the 16-week journey' },
  { placeholder: true, name: 'Ananya V.', initials: 'AV', context: 'School Teacher, 38', program: 'Active Lifestyle', weeks: 12,
    focus: ['Lifestyle', 'Movement'],
    quote: 'Moving every day stopped feeling like a chore. It simply became part of how I live.',
    results: [{ label: 'Daily Steps', before: '3,500', after: '9,000', unit: '' }, { label: 'Waist', before: '36', after: '33', unit: 'in' }],
    story: ['Placeholder story for layout review. Ananya wanted to be more active without adding long workouts to an already full day.', 'Movement was built into the school day and weekends, increasing gradually over twelve weeks.'],
    recorded: 'Recorded across the 12-week journey' }
];
