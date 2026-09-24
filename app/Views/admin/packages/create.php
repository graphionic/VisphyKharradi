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
$mode = 'create';
$package = null;
$featuresList = [];
$gallery = [];
$formAction = site_url('admin/packages');
$submitLabel = 'Create Package';
?>

<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Management</div>
        <h1 class="page-header__title">Create Package</h1>
        <p class="page-header__desc">Add a new program for purchase on Ftpreneur. All pricing is DECIMAL-safe and transactionally consistent.</p>
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

<form method="post" action="<?= esc($formAction, 'attr') ?>" novalidate class="form" id="pkg-create-form" enctype="multipart/form-data">
    <?= view('admin/packages/_form', ['mode'=>$mode, 'package'=>$package, 'featuresList'=>$featuresList, 'gallery'=>$gallery, 'formAction'=>$formAction, 'submitLabel'=>$submitLabel, 'errors'=>$errors]) ?>
</form>

<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
// Vanilla JS — slug, features, media, quill, unsaved
(function(){
    const nameEl = document.getElementById('name');
    const slugEl = document.getElementById('slug');
    let slugManuallyEdited = false;
    if (slugEl) {
        slugEl.addEventListener('input', () => { slugManuallyEdited = true; });
        if (slugEl.value.trim() !== '') slugManuallyEdited = true;
    }
    function slugify(text){
        return text.toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').replace(/-+/g,'-').substring(0,190).replace(/-+$/,'');
    }
    if (nameEl && slugEl) {
        nameEl.addEventListener('input', () => { if (!slugManuallyEdited) slugEl.value = slugify(nameEl.value); });
    }
    // Features
    const list = document.getElementById('feature-list');
    const addBtn = document.getElementById('btn-add-feature');
    const tmpl = document.getElementById('feature-row-template');
    const MAX = 50;
    function updateAriaLabels(){ if (!list) return; list.querySelectorAll('[data-feature-row]').forEach((row, idx)=>{ const input=row.querySelector('input[name="features[]"]'); if(input) input.setAttribute('aria-label','Feature '+(idx+1)); }); }
    function canAdd(){ return list && list.querySelectorAll('[data-feature-row]').length < MAX; }
    function syncAddButton(){ if(addBtn) addBtn.disabled=!canAdd(); }
    if (addBtn && list && tmpl) {
        addBtn.addEventListener('click', ()=>{ if(!canAdd()) return; const clone=tmpl.content.cloneNode(true); list.appendChild(clone); updateAriaLabels(); syncAddButton(); const inputs=list.querySelectorAll('input[name="features[]"]'); if(inputs.length) inputs[inputs.length-1].focus(); dirty=true; });
        list.addEventListener('click', (e)=>{ const remove=e.target.closest('[data-remove-feature]'); const up=e.target.closest('[data-move-up]'); const down=e.target.closest('[data-move-down]'); const row=e.target.closest('[data-feature-row]'); if(!row) return; if(remove){ row.remove(); updateAriaLabels(); syncAddButton(); dirty=true; } else if(up){ const prev=row.previousElementSibling; if(prev){ list.insertBefore(row,prev); updateAriaLabels(); dirty=true; } } else if(down){ const next=row.nextElementSibling; if(next){ list.insertBefore(next,row); updateAriaLabels(); dirty=true; } } });
        let dragSrc=null;
        list.addEventListener('dragstart', (e)=>{ const row=e.target.closest('[data-feature-row]'); if(!row) return; dragSrc=row; row.classList.add('feature-row--dragging'); e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain',''); });
        list.addEventListener('dragend', (e)=>{ const row=e.target.closest('[data-feature-row]'); if(row) row.classList.remove('feature-row--dragging'); });
        list.addEventListener('dragover', (e)=>{ e.preventDefault(); const row=e.target.closest('[data-feature-row]'); if(!row||row===dragSrc) return; const rect=row.getBoundingClientRect(); const midpoint=rect.top+rect.height/2; if(e.clientY<midpoint) list.insertBefore(dragSrc,row); else list.insertBefore(dragSrc,row.nextSibling); updateAriaLabels(); dirty=true; });
        list.addEventListener('drop', (e)=>{ e.preventDefault(); updateAriaLabels(); });
        syncAddButton(); updateAriaLabels();
    }
    // Quill — progressive enhancement: textarea fallback usable by default
    const quillEditor = document.getElementById('quill-editor');
    const quillWrapper = document.getElementById('quill-wrapper');
    const textarea = document.getElementById('full_description');
    let quill = null;
    if (quillEditor && quillWrapper && textarea && window.Quill) {
        try {
            quill = new Quill('#quill-editor', { theme:'snow', modules:{ toolbar:'#quill-toolbar' } });
            const initial = textarea.value;
            if (initial) quill.root.innerHTML = initial;
            quill.on('text-change', ()=>{ textarea.value = quill.root.innerHTML; dirty=true; });
            const form=document.getElementById('pkg-create-form');
            if(form) form.addEventListener('submit', ()=>{ textarea.value = quill.root.innerHTML; });
            // Success — show rich editor, hide fallback textarea
            quillWrapper.style.display = '';
            textarea.style.display = 'none';
        } catch (e) {
            if (quillWrapper) quillWrapper.style.display = 'none';
            if (textarea) textarea.style.display = '';
        }
    }
    // Featured image
    const featuredInput = document.getElementById('featured_image');
    const featuredArea = document.getElementById('featured-upload-area');
    const featuredPreviewNew = document.getElementById('featured-new-preview');
    const btnSelectFeatured = document.getElementById('btn-select-featured');
    const btnRemoveFeaturedNew = document.getElementById('btn-remove-featured-new');
    if (featuredArea && featuredInput) {
        const openPicker = ()=> featuredInput.click();
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
    const galleryInput = document.getElementById('gallery_images');
    const galleryArea = document.getElementById('gallery-upload-area');
    const galleryGrid = document.getElementById('gallery-grid');
    const galleryNewPreview = document.getElementById('gallery-new-preview');
    const btnSelectGallery = document.getElementById('btn-select-gallery');
    const MAX_GALLERY = 10;
    function galleryCount(){ return (galleryGrid?galleryGrid.querySelectorAll('[data-gallery-item]').length:0) + (galleryNewPreview?galleryNewPreview.children.length:0); }
    if (galleryArea && galleryInput) {
        const openGallery=()=> galleryInput.click();
        galleryArea.addEventListener('click', openGallery);
        if(btnSelectGallery) btnSelectGallery.addEventListener('click', (e)=>{ e.stopPropagation(); openGallery(); });
        galleryArea.addEventListener('dragover', (e)=>{ e.preventDefault(); galleryArea.style.borderColor='var(--color-primary)'; });
        galleryArea.addEventListener('dragleave', ()=>{ galleryArea.style.borderColor=''; });
        galleryArea.addEventListener('drop', (e)=>{ e.preventDefault(); galleryArea.style.borderColor=''; if(e.dataTransfer.files.length){ handleGalleryFiles(e.dataTransfer.files); } });
        galleryInput.addEventListener('change', ()=>{ handleGalleryFiles(galleryInput.files); });
        function handleGalleryFiles(files){
            if(!galleryNewPreview) return;
            const remaining = MAX_GALLERY - galleryCount();
            const toAdd = Math.min(files.length, remaining);
            for(let i=0;i<toAdd;i++){
                const file=files[i];
                const card=document.createElement('div');
                card.className='card';
                card.style.cssText='padding:8px; display:grid; gap:8px;';
                card.innerHTML='<div style="width:100%; height:110px; background:var(--color-surface-muted); border-radius:8px; display:grid; place-items:center; font-size:0.75rem; color:var(--color-text-muted);">'+file.name+'</div><div style="font-size:0.70rem; word-break:break-all;">'+file.name+' ('+Math.round(file.size/1024)+' KB)</div><button type="button" class="btn btn--ghost btn--sm" data-gallery-new-remove>Remove</button>';
                // Preview image
                const reader=new FileReader();
                reader.onload=(e)=>{
                    const img=document.createElement('img');
                    img.src=e.target.result;
                    img.style.cssText='width:100%; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--color-border);';
                    const div=card.querySelector('div');
                    if(div) div.replaceWith(img);
                };
                reader.readAsDataURL(file);
                galleryNewPreview.appendChild(card);
            }
            if(files.length>remaining) alert('Maximum 10 gallery images allowed. Only first '+remaining+' added.');
            // Clear input so same file can be selected again
            galleryInput.value='';
            dirty=true;
        }
        // Remove new preview
        if(galleryNewPreview) galleryNewPreview.addEventListener('click', (e)=>{
            const btn=e.target.closest('[data-gallery-new-remove]');
            if(btn){ btn.closest('.card').remove(); dirty=true; }
        });
        // Existing gallery drag and buttons
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
            galleryGrid.addEventListener('dragstart', (e)=>{
                const item=e.target.closest('[data-gallery-item]');
                if(!item) return;
                dragSrc=item;
                e.dataTransfer.effectAllowed='move';
            });
            galleryGrid.addEventListener('dragover', (e)=>{
                e.preventDefault();
                const item=e.target.closest('[data-gallery-item]');
                if(!item||item===dragSrc) return;
                const rect=item.getBoundingClientRect();
                const mid=rect.left+rect.width/2;
                if(e.clientX<mid) galleryGrid.insertBefore(dragSrc,item);
                else galleryGrid.insertBefore(dragSrc,item.nextSibling);
                dirty=true;
            });
        }
    }
    let dirty=false;
    const form=document.getElementById('pkg-create-form');
    if(form){
        form.addEventListener('input', ()=>{ dirty=true; });
        form.addEventListener('change', ()=>{ dirty=true; });
        form.addEventListener('submit', ()=>{ dirty=false; if(quill) textarea.value=quill.root.innerHTML; });
        window.addEventListener('beforeunload', (e)=>{ if(!dirty) return; e.preventDefault(); e.returnValue=''; });
    }
})();
</script>

<?= $this->endSection() ?>
