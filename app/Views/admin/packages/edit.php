<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('head') ?>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
.pkg-create__layout { display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start; }
@media (max-width:960px){ .pkg-create__layout{grid-template-columns:1fr;} }
.pkg-create__main{display:grid; gap:20px;}
.pkg-create__side{display:grid; gap:20px; position:sticky; top:16px;}
@media (max-width:960px){ .pkg-create__side{position:static;} }
.feature-list{display:grid; gap:10px;}
.feature-row{display:grid; grid-template-columns:1fr auto; gap:8px; align-items:center;}
.feature-row__input{display:flex; gap:8px; align-items:center;}
.feature-row__actions{display:flex; gap:6px; align-items:center;}
.feature-row--dragging{opacity:0.5;}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$errors = $errors ?? [];
$mode = 'edit';
$featuresList = $features ?? [];
$package = $package ?? [];
$gallery = $gallery ?? [];
$formAction = site_url('admin/packages/' . ($package['id'] ?? ''));
$submitLabel = 'Save Changes';
?>

<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Management</div>
        <h1 class="page-header__title">Edit Package</h1>
        <?php if (!empty($package['name'])): ?>
            <p class="page-header__desc">Editing <strong><?= esc($package['name']) ?></strong> — all pricing is DECIMAL-safe and transactionally consistent.</p>
        <?php else: ?>
            <p class="page-header__desc">Update package details. All pricing is DECIMAL-safe and transactionally consistent.</p>
        <?php endif; ?>
    </div>
    <div class="page-header__actions">
        <a href="<?= esc(site_url('admin/packages'), 'attr') ?>" class="btn btn--ghost">Cancel</a>
    </div>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert--danger" role="alert" style="margin-bottom:16px;">
        <div class="alert__content"><?= esc(session()->getFlashdata('error')) ?></div>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert--success" role="status" style="margin-bottom:16px;">
        <div class="alert__content"><?= esc(session()->getFlashdata('message')) ?></div>
    </div>
<?php endif; ?>
<?php
$displayErrors = $errors;
if (isset($validation) && $validation instanceof \CodeIgniter\Validation\Validation) {
    $displayErrors = array_merge($displayErrors, $validation->getErrors());
}
$flashErrors = session()->getFlashdata('errors');
if (is_array($flashErrors) && !empty($flashErrors)) $displayErrors = array_merge($displayErrors, $flashErrors);
$hasGeneral = !empty($displayErrors) && !isset($displayErrors['features_details']);
?>
<?php if ($hasGeneral): ?>
    <div class="alert alert--danger" role="alert" style="margin-bottom:16px;">
        <div class="alert__title">Please correct the highlighted fields.</div>
        <ul style="margin:6px 0 0 16px; padding:0; display:grid; gap:4px; font-size:0.875rem;">
            <?php foreach ($displayErrors as $field => $msg): ?>
                <?php if ($field === 'features_details') continue; ?>
                <?php if ($field === 'exception') continue; ?>
                <li><?= esc($msg) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= esc($formAction, 'attr') ?>" novalidate class="form" id="pkg-edit-form" enctype="multipart/form-data">
    <?= view('admin/packages/_form', ['mode'=>$mode, 'package'=>$package, 'featuresList'=>$featuresList, 'gallery'=>$gallery, 'formAction'=>$formAction, 'submitLabel'=>$submitLabel, 'errors'=>$errors]) ?>
</form>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
(function(){
    const nameEl = document.getElementById('name');
    const slugEl = document.getElementById('slug');
    let slugManuallyEdited = false;
    if (slugEl) { slugEl.addEventListener('input', ()=>{ slugManuallyEdited=true; }); if(slugEl.value.trim()!=='') slugManuallyEdited=true; }
    function slugify(text){ return text.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').replace(/-+/g,'-').substring(0,190).replace(/-+$/,''); }
    if(nameEl&&slugEl){ nameEl.addEventListener('input', ()=>{ if(!slugManuallyEdited) slugEl.value=slugify(nameEl.value); }); }
    const list=document.getElementById('feature-list');
    const addBtn=document.getElementById('btn-add-feature');
    const tmpl=document.getElementById('feature-row-template');
    const MAX=50;
    function updateAriaLabels(){ if(!list) return; list.querySelectorAll('[data-feature-row]').forEach((row,idx)=>{ const input=row.querySelector('input[name="features[]"]'); if(input) input.setAttribute('aria-label','Feature '+(idx+1)); }); }
    function canAdd(){ return list && list.querySelectorAll('[data-feature-row]').length < MAX; }
    function syncAddButton(){ if(addBtn) addBtn.disabled=!canAdd(); }
    if(addBtn&&list&&tmpl){
        addBtn.addEventListener('click', ()=>{ if(!canAdd()) return; const clone=tmpl.content.cloneNode(true); list.appendChild(clone); updateAriaLabels(); syncAddButton(); const inputs=list.querySelectorAll('input[name="features[]"]'); if(inputs.length) inputs[inputs.length-1].focus(); dirty=true; });
        list.addEventListener('click', (e)=>{ const remove=e.target.closest('[data-remove-feature]'); const up=e.target.closest('[data-move-up]'); const down=e.target.closest('[data-move-down]'); const row=e.target.closest('[data-feature-row]'); if(!row) return; if(remove){ row.remove(); updateAriaLabels(); syncAddButton(); dirty=true; } else if(up){ const prev=row.previousElementSibling; if(prev){ list.insertBefore(row,prev); updateAriaLabels(); dirty=true; } } else if(down){ const next=row.nextElementSibling; if(next){ list.insertBefore(next,row); updateAriaLabels(); dirty=true; } } });
        let dragSrc=null;
        list.addEventListener('dragstart', (e)=>{ const row=e.target.closest('[data-feature-row]'); if(!row) return; dragSrc=row; row.classList.add('feature-row--dragging'); e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain',''); });
        list.addEventListener('dragend', (e)=>{ const row=e.target.closest('[data-feature-row]'); if(row) row.classList.remove('feature-row--dragging'); });
        list.addEventListener('dragover', (e)=>{ e.preventDefault(); const row=e.target.closest('[data-feature-row]'); if(!row||row===dragSrc) return; const rect=row.getBoundingClientRect(); const mid=rect.top+rect.height/2; if(e.clientY<mid) list.insertBefore(dragSrc,row); else list.insertBefore(dragSrc,row.nextSibling); updateAriaLabels(); dirty=true; });
        list.addEventListener('drop', (e)=>{ e.preventDefault(); updateAriaLabels(); });
        syncAddButton(); updateAriaLabels();
    }
    // Quill — progressive enhancement: textarea fallback usable by default
    const quillEditor=document.getElementById('quill-editor');
    const quillWrapper=document.getElementById('quill-wrapper');
    const textarea=document.getElementById('full_description');
    let quill=null;
    if(quillEditor&&quillWrapper&&textarea&&window.Quill){
        try {
            quill=new Quill('#quill-editor', { theme:'snow', modules:{ toolbar:'#quill-toolbar' } });
            const initial=textarea.value;
            if(initial) quill.root.innerHTML=initial;
            quill.on('text-change', ()=>{ textarea.value=quill.root.innerHTML; dirty=true; });
            const form=document.getElementById('pkg-edit-form');
            if(form) form.addEventListener('submit', ()=>{ textarea.value=quill.root.innerHTML; });
            // Success — show rich editor, hide fallback textarea
            quillWrapper.style.display='';
            textarea.style.display='none';
        } catch (e) {
            if(quillWrapper) quillWrapper.style.display='none';
            if(textarea) textarea.style.display='';
        }
    }
    // Featured
    const featuredInput=document.getElementById('featured_image');
    const featuredArea=document.getElementById('featured-upload-area');
    const featuredPreviewNew=document.getElementById('featured-new-preview');
    const btnSelectFeatured=document.getElementById('btn-select-featured');
    const btnRemoveFeaturedNew=document.getElementById('btn-remove-featured-new');
    if(featuredArea&&featuredInput){
        const openPicker=()=> featuredInput.click();
        featuredArea.addEventListener('click', (e)=>{ if(e.target.closest('button')||e.target.closest('input')) return; openPicker(); });
        if(btnSelectFeatured) btnSelectFeatured.addEventListener('click', (e)=>{ e.stopPropagation(); openPicker(); });
        featuredArea.addEventListener('dragover', (e)=>{ e.preventDefault(); featuredArea.style.borderColor='var(--color-primary)'; });
        featuredArea.addEventListener('dragleave', ()=>{ featuredArea.style.borderColor=''; });
        featuredArea.addEventListener('drop', (e)=>{ e.preventDefault(); featuredArea.style.borderColor=''; if(e.dataTransfer.files.length){ featuredInput.files=e.dataTransfer.files; updateFeaturedPreview(); } });
        featuredInput.addEventListener('change', updateFeaturedPreview);
        function updateFeaturedPreview(){
            if(!featuredInput.files.length){ if(featuredPreviewNew) featuredPreviewNew.style.display='none'; if(btnRemoveFeaturedNew) btnRemoveFeaturedNew.style.display='none'; return; }
            const file=featuredInput.files[0];
            if(featuredPreviewNew){
                featuredPreviewNew.style.display='block';
                featuredPreviewNew.innerHTML='<div style="border:1px solid var(--color-border); border-radius:8px; padding:8px; display:inline-block;"><div style="font-size:0.75rem; margin-bottom:6px;">New: '+file.name+' ('+Math.round(file.size/1024)+' KB)</div><img style="max-width:220px; max-height:140px; object-fit:cover; border-radius:6px;" alt="Preview"></div>';
                const img=featuredPreviewNew.querySelector('img');
                const reader=new FileReader();
                reader.onload=(e)=>{ img.src=e.target.result; };
                reader.readAsDataURL(file);
            }
            if(btnRemoveFeaturedNew) btnRemoveFeaturedNew.style.display='inline-flex';
            dirty=true;
        }
        if(btnRemoveFeaturedNew) btnRemoveFeaturedNew.addEventListener('click', ()=>{ featuredInput.value=''; if(featuredPreviewNew) featuredPreviewNew.style.display='none'; btnRemoveFeaturedNew.style.display='none'; dirty=true; });
    }
    // Gallery
    const galleryInput=document.getElementById('gallery_images');
    const galleryArea=document.getElementById('gallery-upload-area');
    const galleryGrid=document.getElementById('gallery-grid');
    const galleryNewPreview=document.getElementById('gallery-new-preview');
    const btnSelectGallery=document.getElementById('btn-select-gallery');
    const MAX_GALLERY=10;
    function galleryCount(){ return (galleryGrid?galleryGrid.querySelectorAll('[data-gallery-item]').length:0)+(galleryNewPreview?galleryNewPreview.children.length:0); }
    if(galleryArea&&galleryInput){
        const openGallery=()=> galleryInput.click();
        galleryArea.addEventListener('click', openGallery);
        if(btnSelectGallery) btnSelectGallery.addEventListener('click', (e)=>{ e.stopPropagation(); openGallery(); });
        galleryArea.addEventListener('dragover', (e)=>{ e.preventDefault(); galleryArea.style.borderColor='var(--color-primary)'; });
        galleryArea.addEventListener('dragleave', ()=>{ galleryArea.style.borderColor=''; });
        galleryArea.addEventListener('drop', (e)=>{ e.preventDefault(); galleryArea.style.borderColor=''; if(e.dataTransfer.files.length) handleGalleryFiles(e.dataTransfer.files); });
        galleryInput.addEventListener('change', ()=>{ handleGalleryFiles(galleryInput.files); });
        function handleGalleryFiles(files){
            if(!galleryNewPreview) return;
            const remaining=MAX_GALLERY-galleryCount();
            const toAdd=Math.min(files.length, remaining);
            for(let i=0;i<toAdd;i++){
                const file=files[i];
                const card=document.createElement('div');
                card.className='card';
                card.style.cssText='padding:8px; display:grid; gap:8px;';
                card.innerHTML='<div style="width:100%; height:110px; background:var(--color-surface-muted); border-radius:8px; display:grid; place-items:center; font-size:0.75rem; color:var(--color-text-muted);">'+file.name+'</div><div style="font-size:0.70rem; word-break:break-all;">'+file.name+' ('+Math.round(file.size/1024)+' KB)</div><button type="button" class="btn btn--ghost btn--sm" data-gallery-new-remove>Remove</button>';
                const reader=new FileReader();
                reader.onload=(e)=>{ const img=document.createElement('img'); img.src=e.target.result; img.style.cssText='width:100%; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--color-border);'; const div=card.querySelector('div'); if(div) div.replaceWith(img); };
                reader.readAsDataURL(file);
                galleryNewPreview.appendChild(card);
            }
            if(files.length>remaining) alert('Maximum 10 gallery images allowed. Only first '+remaining+' added.');
            galleryInput.value='';
            dirty=true;
        }
        if(galleryNewPreview) galleryNewPreview.addEventListener('click', (e)=>{ const btn=e.target.closest('[data-gallery-new-remove]'); if(btn){ btn.closest('.card').remove(); dirty=true; } });
        if(galleryGrid){
            galleryGrid.addEventListener('click', (e)=>{
                const left=e.target.closest('[data-gallery-left]');
                const right=e.target.closest('[data-gallery-right]');
                const remove=e.target.closest('[data-gallery-remove]');
                const item=e.target.closest('[data-gallery-item]');
                if(!item) return;
                if(remove){ item.remove(); dirty=true; }
                else if(left){ const prev=item.previousElementSibling; if(prev) galleryGrid.insertBefore(item,prev); dirty=true; }
                else if(right){ const next=item.nextElementSibling; if(next) galleryGrid.insertBefore(next,item); else galleryGrid.appendChild(item); dirty=true; }
            });
            let dragSrc=null;
            galleryGrid.addEventListener('dragstart', (e)=>{ const item=e.target.closest('[data-gallery-item]'); if(!item) return; dragSrc=item; e.dataTransfer.effectAllowed='move'; });
            galleryGrid.addEventListener('dragover', (e)=>{ e.preventDefault(); const item=e.target.closest('[data-gallery-item]'); if(!item||item===dragSrc) return; const rect=item.getBoundingClientRect(); const mid=rect.left+rect.width/2; if(e.clientX<mid) galleryGrid.insertBefore(dragSrc,item); else galleryGrid.insertBefore(dragSrc,item.nextSibling); dirty=true; });
        }
    }
    let dirty=false;
    const form=document.getElementById('pkg-edit-form');
    if(form){
        form.addEventListener('input', ()=>{ dirty=true; });
        form.addEventListener('change', ()=>{ dirty=true; });
        form.addEventListener('submit', ()=>{ dirty=false; if(quill) textarea.value=quill.root.innerHTML; });
        window.addEventListener('beforeunload', (e)=>{ if(!dirty) return; e.preventDefault(); e.returnValue=''; });
    }

    // Dynamic Duration & Pricing Options
    const optContainer = document.getElementById('options-container');
    const optEmptyState = document.getElementById('options-empty-state');
    const btnAddOption = document.getElementById('btn-add-option');

    function updateOptEmptyState() {
        if (!optContainer || !optEmptyState) return;
        const count = optContainer.querySelectorAll('.option-card').length;
        optEmptyState.style.display = count === 0 ? 'block' : 'none';
    }

    function reindexOptions() {
        if (!optContainer) return;
        const cards = optContainer.querySelectorAll('.option-card');
        cards.forEach((card, optIdx) => {
            const inputs = card.querySelectorAll('[name^="options["]');
            inputs.forEach(input => {
                const nameAttr = input.getAttribute('name');
                if (nameAttr) {
                    const newName = nameAttr.replace(/^options\[\d+\]/, 'options[' + optIdx + ']');
                    input.setAttribute('name', newName);
                }
            });
        });
        updateOptEmptyState();
    }

    function updateHeaderSummary(card) {
        const nameInput = card.querySelector('.option-name-input');
        const priceInput = card.querySelector('.option-price-input');
        const valInput = card.querySelector('.option-dur-val-input');
        const unitInput = card.querySelector('.option-dur-unit-input');
        const activeInput = card.querySelector('.option-active-input');

        const titleDisplay = card.querySelector('.option-title-display');
        const subtitleDisplay = card.querySelector('.option-subtitle-display');
        const badgeDisplay = card.querySelector('.option-active-badge');

        const name = nameInput ? nameInput.value.trim() : '';
        const price = priceInput ? priceInput.value.trim() : '';
        const val = valInput ? valInput.value.trim() : '3';
        const unit = unitInput && unitInput.selectedIndex >= 0 ? unitInput.options[unitInput.selectedIndex].text : 'Months';
        const isActive = activeInput ? activeInput.checked : true;

        if (titleDisplay) {
            titleDisplay.textContent = name !== '' ? name : 'New Duration Option';
        }
        if (subtitleDisplay) {
            let priceText = price !== '' ? ' — ₹' + Number(price).toLocaleString('en-IN') : '';
            subtitleDisplay.textContent = (val !== '' ? val : '0') + ' ' + unit + priceText;
        }
        if (badgeDisplay) {
            badgeDisplay.textContent = isActive ? 'Active' : 'Inactive';
            badgeDisplay.className = 'badge option-active-badge ' + (isActive ? 'badge--success' : 'badge--neutral');
        }
    }

    function bindOptionEvents(card) {
        const toggleBtn = card.querySelector('[data-toggle-option]');
        const body = card.querySelector('.option-card__body');
        const chevron = card.querySelector('.option-chevron');

        if (toggleBtn && body) {
            toggleBtn.addEventListener('click', function(e) {
                const isHidden = body.style.display === 'none';
                body.style.display = isHidden ? 'grid' : 'none';
                if (chevron) {
                    chevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
        }

        const inputs = card.querySelectorAll('.option-name-input, .option-price-input, .option-dur-val-input, .option-dur-unit-input, .option-active-input');
        inputs.forEach(input => {
            input.addEventListener('input', () => { updateHeaderSummary(card); dirty = true; });
            input.addEventListener('change', () => { updateHeaderSummary(card); dirty = true; });
        });

        const removeBtn = card.querySelector('[data-remove-option]');
        if (removeBtn) {
            removeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (confirm('Remove this duration option?')) {
                    card.remove();
                    reindexOptions();
                    dirty = true;
                }
            });
        }

        const moveUp = card.querySelector('[data-move-option-up]');
        if (moveUp) {
            moveUp.addEventListener('click', function(e) {
                e.stopPropagation();
                const prev = card.previousElementSibling;
                if (prev && prev.classList.contains('option-card')) {
                    optContainer.insertBefore(card, prev);
                    reindexOptions();
                    dirty = true;
                }
            });
        }

        const moveDown = card.querySelector('[data-move-option-down]');
        if (moveDown) {
            moveDown.addEventListener('click', function(e) {
                e.stopPropagation();
                const next = card.nextElementSibling;
                if (next && next.classList.contains('option-card')) {
                    optContainer.insertBefore(next, card);
                    reindexOptions();
                    dirty = true;
                }
            });
        }

        const btnAddInclusion = card.querySelector('[data-add-option-inclusion]');
        const inclusionsList = card.querySelector('.option-inclusions-list');

        if (btnAddInclusion && inclusionsList) {
            btnAddInclusion.addEventListener('click', function() {
                const optIdx = Array.from(optContainer.querySelectorAll('.option-card')).indexOf(card);
                const row = document.createElement('div');
                row.className = 'option-inclusion-row';
                row.style.cssText = 'display:flex; gap:8px; align-items:center;';
                row.innerHTML = `
                    <input class="field__input" name="options[${optIdx}][features][]" type="text" value="" maxlength="300" placeholder="e.g. 2 video calls with Visphy Kharradi">
                    <button type="button" class="btn btn--ghost btn--sm" data-remove-option-inclusion style="color:var(--color-danger);">Remove</button>
                `;
                inclusionsList.appendChild(row);
                const input = row.querySelector('input');
                if (input) input.focus();
                dirty = true;
            });
        }

        card.addEventListener('click', function(e) {
            if (e.target && e.target.matches('[data-remove-option-inclusion]')) {
                const row = e.target.closest('.option-inclusion-row');
                if (row) { row.remove(); dirty = true; }
            }
        });
    }

    if (optContainer) {
        optContainer.querySelectorAll('.option-card').forEach(card => bindOptionEvents(card));
    }

    if (btnAddOption && optContainer) {
        btnAddOption.addEventListener('click', function() {
            const count = optContainer.querySelectorAll('.option-card').length;
            const newIndex = count;

            const card = document.createElement('div');
            card.className = 'card option-card';
            card.setAttribute('data-option-item', '');
            card.style.cssText = 'border:1px solid var(--color-border); border-radius:8px; overflow:hidden;';
            card.innerHTML = `
                <div class="option-card__header" data-toggle-option style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:var(--color-surface-subtle); cursor:pointer; user-select:none;">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span class="option-chevron" style="display:inline-flex; align-items:center; transition:transform 0.2s;">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <div>
                            <strong class="option-title-display">New Duration Option</strong>
                            <span class="badge option-active-badge badge--success" style="margin-left:8px;">Active</span>
                            <div class="field__hint option-subtitle-display" style="margin:2px 0 0 0;">3 Months</div>
                        </div>
                    </div>
                    <div style="display:flex; gap:6px; align-items:center;" onclick="event.stopPropagation();">
                        <button type="button" class="icon-btn icon-btn--sm" data-move-option-up aria-label="Move option up" title="Move up">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 14L6 10l1.4-1.4L10 11.2l2.6-2.6L14 10l-4 4Z" fill="currentColor"/></svg>
                        </button>
                        <button type="button" class="icon-btn icon-btn--sm" data-move-option-down aria-label="Move option down" title="Move down">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 6l4 4-1.4 1.4L10 8.8 7.4 11.4 6 10l4-4Z" fill="currentColor"/></svg>
                        </button>
                        <button type="button" class="btn btn--ghost btn--sm" data-remove-option style="color:var(--color-danger);">Remove</button>
                    </div>
                </div>
                <div class="option-card__body" style="padding:16px; display:grid; gap:16px; border-top:1px solid var(--color-border-subtle);">
                    <input type="hidden" name="options[${newIndex}][id]" value="">
                    <div class="form__row form__row--2">
                        <div class="field">
                            <label class="field__label">Option Name <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                            <input class="field__input option-name-input" name="options[${newIndex}][name]" type="text" value="" placeholder="e.g. 3 Month Program" required>
                        </div>
                        <div class="field">
                            <label class="field__label">Price (₹) <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                            <input class="field__input option-price-input" name="options[${newIndex}][price]" type="number" step="0.01" min="0" value="" placeholder="50000" required>
                        </div>
                    </div>

                    <div class="form__row form__row--2">
                        <div class="field">
                            <label class="field__label">Duration Value <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                            <input class="field__input option-dur-val-input" name="options[${newIndex}][duration_value]" type="number" min="1" step="1" value="3" placeholder="3" required>
                        </div>
                        <div class="field">
                            <label class="field__label">Duration Unit <span aria-hidden="true" style="color:var(--color-danger)">*</span></label>
                            <select class="field__input option-dur-unit-input" name="options[${newIndex}][duration_unit]" required>
                                <option value="day">Days</option>
                                <option value="week">Weeks</option>
                                <option value="month" selected>Months</option>
                                <option value="year">Years</option>
                            </select>
                        </div>
                    </div>

                    <div class="field">
                        <label class="field__label">Short Description</label>
                        <textarea class="field__input" name="options[${newIndex}][short_description]" rows="2" placeholder="Brief overview for this option..."></textarea>
                    </div>

                    <div class="field">
                        <label style="display:flex; gap:8px; align-items:center; cursor:pointer;">
                            <input type="checkbox" class="option-active-input" name="options[${newIndex}][is_active]" value="1" checked style="width:16px;height:16px;">
                            <span class="field__label" style="margin:0;">Active (Enabled for selection)</span>
                        </label>
                    </div>

                    <div style="border:1px solid var(--color-border-subtle); border-radius:6px; padding:12px; background:var(--color-surface-subtle);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <label class="field__label" style="margin:0; font-weight:600;">What's Included <span class="u-muted" style="font-weight:normal;">(Option features)</span></label>
                            <button type="button" class="btn btn--secondary btn--sm" data-add-option-inclusion>+ Add Inclusion</button>
                        </div>
                        <div class="option-inclusions-list" style="display:grid; gap:8px;">
                            <div class="option-inclusion-row" style="display:flex; gap:8px; align-items:center;">
                                <input class="field__input" name="options[${newIndex}][features][]" type="text" value="" maxlength="300" placeholder="e.g. 2 video calls with Visphy Kharradi">
                                <button type="button" class="btn btn--ghost btn--sm" data-remove-option-inclusion style="color:var(--color-danger);">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            optContainer.appendChild(card);
            bindOptionEvents(card);
            reindexOptions();
            dirty = true;

            const nameInput = card.querySelector('.option-name-input');
            if (nameInput) nameInput.focus();
        });
    }
})();
</script>

<?= $this->endSection() ?>
