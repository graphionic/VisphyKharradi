<?php

namespace App\Controllers;

/**
 * Home Controller — Public Root
 *
 * Renders public frontend layout and active page views.
 * Phase 6A: Frontend Foundation
 */
class Home extends BaseController
{
    public function index(): string
    {
        return view('frontend/pages/home', [
            'title'            => "Ftpreneur — Visphy Kharradi's Nutrition, Strength & Disease Management",
            'meta_description' => "Transform your health through scientific nutrition, strength training, and lifestyle disease management with Visphy Kharradi.",
            'canonical_url'    => base_url('/'),
        ]);
    }
}
