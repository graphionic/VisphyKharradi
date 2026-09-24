<?php

namespace App\Controllers;

/**
 * Home Controller — Phase 1 Foundation
 *
 * Phase 1 only: renders temporary foundation view to prove
 * request → route → controller → view works.
 * No business logic, no package loading.
 */
class Home extends BaseController
{
    public function index(): string
    {
        return view('home/foundation');
    }
}
