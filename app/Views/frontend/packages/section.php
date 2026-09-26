<?php
/**
 * Frontend Package Display Section Router
 *
 * Active design resolved via $activeDesign (setting: frontend.package_design).
 * Available designs: concept_01, concept_02, concept_04.
 * Default & Step 2 Production Renderer: concept_02.
 * Fallback: concept_02 (for concept_01/04 until their production renderers are built).
 */
$design = $activeDesign ?? 'concept_02';

switch ($design) {
    case 'concept_01':
        echo $this->include('frontend/packages/concept_01');
        break;
    case 'concept_04':
        echo $this->include('frontend/packages/concept_04');
        break;
    case 'concept_02':
    default:
        echo $this->include('frontend/packages/concept_02');
        break;
}
