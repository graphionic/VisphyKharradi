/* FTPRENEUR — Package Experience · Concept 01 — MOCK DATA ONLY
   Prototype copy. No medical cure / reversal claims. Prices are placeholders.
   accent by category: weight → ultra · metabolic → mint · strength → marigold
                       nutrition → mint · lifestyle → ultra
   focus [nutrition, strength, lifestyle]: 3 lead · 2 core · 1 support          */
(function () {
  var ACCENT = { Weight: "ultra", Metabolic: "mint", Strength: "marigold", Nutrition: "mint", Lifestyle: "ultra" };

  var P = [
    ["Sustainable Weight Reset", "Weight", 12, 14999, [3, 2, 2], "Lose weight at a pace your routine can actually hold — no crash diets.", "Weight management · steady, long-term change"],
    ["Metabolic Health Program", "Metabolic", 12, 17999, [3, 2, 3], "Food, movement and routine structured to support better metabolic health.", "Blood sugar, cholesterol or metabolic concerns — alongside your doctor", true],
    ["Strength Foundation", "Strength", 8, 9999, [2, 3, 1], "Learn to train with confidence — technique first, progress that fits your body.", "Beginners or anyone returning to training"],
    ["Nutrition Reset", "Nutrition", 6, 7999, [3, 1, 2], "Rebuild how you eat around the food you already love and cook.", "Irregular eating, low energy, confusing diet advice"],
    ["Active Lifestyle", "Lifestyle", 8, 8999, [1, 2, 3], "Move more through the day with habits that survive a busy calendar.", "Desk-bound routines and low daily activity"],
    ["Complete Transformation", "Weight", 24, 34999, [3, 3, 3], "Six months of fully personalised nutrition, strength and lifestyle coaching.", "Ready for a committed, long-term change"],
    ["Mobility & Strength", "Strength", 10, 11999, [1, 3, 2], "Move freely and get stronger — mobility and strength built together.", "Stiffness, posture issues, low-impact preference"],
    ["Healthy Routine Reset", "Lifestyle", 6, 6999, [2, 1, 3], "Sleep, meals and movement reorganised into a routine that feels lighter.", "Chaotic schedules and late nights"],
    ["Performance Nutrition", "Nutrition", 12, 13999, [3, 2, 1], "Fuel your training — nutrition planned around performance and recovery.", "Active people who train regularly"],
    ["Long-Term Wellness", "Lifestyle", 48, 49999, [3, 3, 3], "A year of guidance that evolves with your goals, seasons and life.", "Anyone who values continuity with one team"],
    ["Body Composition", "Weight", 16, 19999, [3, 3, 1], "Lose fat, keep muscle — structured strength and nutrition for recomposition.", "Some training experience, clear composition goals"],
    ["Strength + Nutrition", "Strength", 12, 16999, [3, 3, 1], "Two pillars, one plan — progressive training matched with precise nutrition.", "Wanting visible strength progress"],
    ["Blood Sugar Balance", "Metabolic", 16, 18999, [3, 2, 2], "Meal timing, movement and habits to support blood-sugar management.", "Managing type 2 diabetes or prediabetes — alongside your doctor"],
    ["Thyroid-Aware Nutrition", "Metabolic", 12, 14999, [3, 2, 2], "A plan paced around energy, medication timing and daily routine.", "Managing a thyroid condition — alongside your doctor"],
    ["PCOS Lifestyle Support", "Metabolic", 16, 17999, [3, 2, 3], "Strength, food and routine support for women managing PCOS / PCOD.", "Women managing PCOS / PCOD"],
    ["Busy Professional Reset", "Lifestyle", 8, 10999, [2, 2, 3], "Health that fits meetings, travel and eating out — not the other way round.", "Long hours, frequent travel"],
    ["Beginner Gym Start", "Strength", 6, 6999, [1, 3, 1], "Your first six weeks in the gym, planned and coached properly.", "First-time gym members"],
    ["Family Kitchen Plan", "Nutrition", 8, 9999, [3, 1, 2], "One kitchen, one plan — balanced meals for everyone at the table.", "Families cooking together"],
    ["Fat Loss for Life", "Weight", 20, 24999, [3, 2, 3], "A longer runway for fat loss that builds habits you keep afterwards.", "Anyone tired of short-term results"],
    ["50+ Strength & Balance", "Strength", 12, 13999, [2, 3, 2], "Stay strong, steady and independent — training designed for 50 and beyond.", "Adults aged 50 and above"]
  ];

  var WORK = ["Nutrition", "Strength training", "Daily movement", "Sleep & routine", "Lifestyle consistency"];

  window.PK_PROGRAMS = P.map(function (p, i) {
    var f = p[4];
    return {
      n: i + 1,
      slug: p[0].toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, ""),
      name: p[0],
      category: p[1],
      accent: ACCENT[p[1]],
      weeks: p[2],
      price: p[3],
      focus: f,
      purpose: p[5],
      suitable: p[6],
      featured: !!p[7],
      medical: p[1] === "Metabolic",
      overview: "Every plan starts with a 1:1 assessment of your history, routine, food habits and goals — then it is built around you and adjusted as you progress. Nothing is copied from a template.",
      work: WORK.map(function (w, k) {
        var lvl = [f[0], f[1], Math.max(1, f[2] - 1), f[2], Math.max(f[2], 2)][k];
        return { name: w, level: lvl };
      }),
      includes: [
        { t: "Personalised plan", d: "Built from your assessment — never a template." },
        { t: "Progress reviews", d: "Every two weeks, with adjustments as you go." },
        { t: "Nutrition guidance", d: "Home-food friendly portions, timing and swaps." },
        { t: "Strength guidance", d: "Sessions matched to your level, with form feedback." },
        { t: "Lifestyle framework", d: "Sleep, stress and daily-movement structure." },
        { t: "Support & follow-up", d: "WhatsApp access for everyday questions." }
      ]
    };
  });
})();
