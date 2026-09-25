<?= $this->extend('admin/layouts/app') ?>

<?= $this->section('content') ?>
<?php
use App\Services\PackageService;
$hasFilters = ($filters['q'] !== '' || $filters['status'] !== 'all' || $filters['sort'] !== 'display_order');
$hasPackages = !empty($packages);
$isFilteredEmpty = !$hasPackages && $hasFilters;
$isDbEmpty = !$hasPackages && !$hasFilters;
$canReorder = $filters['sort'] === 'display_order' && $filters['q'] === '' && $filters['status'] === 'all' && $total > 1;
?>
<div class="page-header">
    <div class="page-header__meta">
        <div class="page-header__eyebrow">Management</div>
        <h1 class="page-header__title">Packages</h1>
        <p class="page-header__desc">Manage the programs available for purchase on Ftpreneur.</p>
    </div>
    <div class="page-header__actions" style="display:flex; gap:8px;">
        <a href="<?= esc(site_url('admin/packages/deleted'), 'attr') ?>" class="btn btn--ghost">Deleted</a>
        <a href="<?= esc(site_url('admin/packages/create'), 'attr') ?>" class="btn btn--primary">
            <svg width="14" height="14" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4v12M4 10h12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            Add Package
        </a>
    </div>
</div>

<div class="u-stack-lg">

    <!-- Filter toolbar -->
    <section class="card">
        <div class="card__body">
            <form method="get" action="<?= esc(site_url('admin/packages'), 'attr') ?>" class="u-stack" style="gap:14px;">
                <div style="display:grid; grid-template-columns: 1fr 160px 180px auto; gap:12px; align-items:end;">
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="q">Search</label>
                        <input class="field__input" id="q" name="q" type="search" placeholder="Search packages..." value="<?= esc($filters['q']) ?>" maxlength="190" autocomplete="off" aria-label="Search packages by name or slug">
                    </div>
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="status">Status</label>
                        <select class="field__input" id="status" name="status" aria-label="Filter by status">
                            <option value="all" <?= $filters['status']==='all' ? 'selected' : '' ?>>All</option>
                            <option value="active" <?= $filters['status']==='active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $filters['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="field" style="margin:0;">
                        <label class="field__label" for="sort">Sort</label>
                        <select class="field__input" id="sort" name="sort" aria-label="Sort packages">
                            <?php foreach ($sortOptions as $key => $label): ?>
                                <option value="<?= esc($key, 'attr') ?>" <?= $filters['sort']===$key ? 'selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="u-flex" style="gap:8px;">
                        <button type="submit" class="btn btn--primary">Apply</button>
                        <?php if ($hasFilters): ?>
                            <a href="<?= esc(site_url('admin/packages'), 'attr') ?>" class="btn btn--ghost">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert--success" role="status"><div class="alert__content"><?= esc(session()->getFlashdata('message')) ?></div></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert--danger" role="alert"><div class="alert__content"><?= esc(session()->getFlashdata('error')) ?></div></div>
    <?php endif; ?>

    <?php if ($hasPackages): ?>
        <div class="u-flex-between" style="align-items:center;">
            <div class="small u-muted" aria-live="polite">
                <?php if ($total > $perPage): ?>
                    Showing <?= esc((string)$rangeStart) ?>–<?= esc((string)$rangeEnd) ?> of <?= esc((string)$total) ?> packages
                <?php else: ?>
                    <?= esc((string)$total) ?> <?= $total===1 ? 'package' : 'packages' ?>
                <?php endif; ?>
                <?php if ($filters['q'] !== ''): ?> for “<?= esc($filters['q']) ?>” <?php endif; ?>
            </div>
            <?php if ($canReorder): ?>
                <div style="display:flex; gap:8px;">
                    <button type="button" class="btn btn--secondary btn--sm" id="btnSaveOrder" style="display:none;">Save Order</button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($hasPackages): ?>
        <?php if ($canReorder): ?>
        <form method="post" action="<?= esc(site_url('admin/packages/reorder'), 'attr') ?>" id="reorderForm" style="display:none;">
            <?= csrf_field() ?>
            <input type="hidden" name="ordered_ids" id="orderedIdsInput" value="">
        </form>
        <?php endif; ?>
        <div class="table-wrap" role="region" aria-label="Packages" tabindex="0">
            <table class="table" id="packagesTable">
                <thead>
                    <tr>
                        <?php if ($canReorder): ?><th scope="col" style="width:28px;"></th><?php endif; ?>
                        <th scope="col">Package</th>
                        <th scope="col">Price</th>
                        <th scope="col">Duration</th>
                        <th scope="col" style="text-align:center;">Features</th>
                        <th scope="col">Badge</th>
                        <th scope="col">Status</th>
                        <th scope="col">Featured</th>
                        <th scope="col" style="text-align:right; width:56px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="packagesTbody">
                    <?php foreach ($packages as $pkg): ?>
                        <?php
                            $fid = (int) $pkg['id'];
                            $featCount = $featureCounts[$fid] ?? 0;
                            $optCount = $optionCounts[$fid] ?? 0;
                            $badgeLabel = PackageService::badgeLabel($pkg['badge'] ?? null);
                            $priceSelling = PackageService::formatPrice($pkg['selling_price']);
                            $priceRegular = isset($pkg['regular_price']) && $pkg['regular_price'] !== null && (float)$pkg['regular_price'] > (float)$pkg['selling_price'] ? PackageService::formatPrice($pkg['regular_price']) : null;
                            $duration = PackageService::formatDuration($pkg['duration_value'] ?? null, $pkg['duration_unit'] ?? null);
                            $isActive = (int)($pkg['is_active'] ?? 0) === 1;
                            $isFeatured = (int)($pkg['is_featured'] ?? 0) === 1;
                            $thumb = $pkg['featured_image'] ?? null;
                        ?>
                        <tr data-package-id="<?= esc((string)$fid, 'attr') ?>" <?= $canReorder ? 'draggable="true"' : '' ?> style="<?= $canReorder ? 'cursor:grab;' : '' ?>">
                            <?php if ($canReorder): ?>
                            <td style="text-align:center;">
                                <span aria-hidden="true" style="cursor:grab; user-select:none;">&#x283F;</span>
                                <div style="display:flex; gap:4px; justify-content:center; margin-top:4px;">
                                    <button type="button" class="icon-btn icon-btn--sm" data-move-up aria-label="Move up"><svg width="12" height="12" viewBox="0 0 20 20" fill="none"><path d="M10 6l-5 5 1.4 1.4L10 7.8 13.6 10.4 15 9l-5-3Z" fill="currentColor"/></svg></button>
                                    <button type="button" class="icon-btn icon-btn--sm" data-move-down aria-label="Move down"><svg width="12" height="12" viewBox="0 0 20 20" fill="none"><path d="M10 14l5-5-1.4-1.4L10 11.2 6.4 7.6 5 9l5 5Z" fill="currentColor"/></svg></button>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <div style="width:48px; height:36px; border-radius:6px; overflow:hidden; border:1px solid var(--color-border); background:var(--color-surface-muted); flex-shrink:0; display:grid; place-items:center;">
                                        <?php if (!empty($thumb)): ?>
                                            <img src="<?= esc('/' . ltrim($thumb, '/'), 'attr') ?>" alt="" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true" style="color:var(--color-text-muted)"><rect x="3" y="4" width="14" height="12" rx="1.2" stroke="currentColor" stroke-width="1.2"/><path d="M3 12l4-4 3 3 4-5 3 3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7.5" cy="7.5" r="1" fill="currentColor"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="table__primary"><?= esc($pkg['name']) ?></div>
                                        <?php if (!empty($pkg['short_description'])): ?>
                                            <div class="table__muted" style="max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= esc($pkg['short_description']) ?></div>
                                        <?php else: ?>
                                            <div class="table__muted"><?= esc($pkg['slug']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="table__mono">
                                <span class="table__primary"><?= esc($priceSelling) ?></span>
                                <?php if ($priceRegular): ?>
                                    <span class="table__muted" style="text-decoration:line-through; margin-left:6px; font-size:0.80em;"><?= esc($priceRegular) ?></span>
                                <?php endif; ?>
                                <?php if ($optCount > 0): ?>
                                    <div class="table__muted" style="font-size:0.75rem; margin-top:2px; font-weight:500; font-family:var(--font-sans); color:var(--color-primary-600);"><?= esc((string)$optCount) ?> <?= $optCount === 1 ? 'option' : 'options' ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= esc($duration) ?></td>
                            <td style="text-align:center;">
                                <?php if ($featCount === 0): ?><span class="u-muted">—</span><?php else: ?><?= esc((string)$featCount) ?> <?= $featCount===1 ? 'feature' : 'features' ?><?php endif; ?>
                            </td>
                            <td><?php if ($badgeLabel !== null): ?><span class="badge badge--info"><?= esc($badgeLabel) ?></span><?php else: ?><span class="u-muted">—</span><?php endif; ?></td>
                            <td>
                                <?php if ($isActive): ?><span class="badge badge--success"><span class="badge__dot" aria-hidden="true"></span> Active</span><?php else: ?><span class="badge badge--neutral">Inactive</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isFeatured): ?><span class="badge badge--primary">Featured</span><?php else: ?><span class="u-muted">—</span><?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <div class="dropdown" data-dropdown>
                                    <button class="icon-btn icon-btn--ghost icon-btn--sm" data-dropdown-trigger aria-haspopup="menu" aria-expanded="false" aria-label="Actions for <?= esc($pkg['name'], 'attr') ?>">
                                        <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="4" r="1.5" fill="currentColor"/><circle cx="10" cy="10" r="1.5" fill="currentColor"/><circle cx="10" cy="16" r="1.5" fill="currentColor"/></svg>
                                    </button>
                                    <div class="dropdown__menu" data-dropdown-menu role="menu" aria-label="Package actions" style="min-width:180px;">
                                        <a class="dropdown__item" href="<?= esc(site_url('admin/packages/' . $fid . '/edit'), 'attr') ?>" role="menuitem" data-dropdown-item>Edit</a>
                                        <form method="post" action="<?= esc(site_url('admin/packages/' . $fid . '/toggle-active'), 'attr') ?>" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dropdown__item" role="menuitem" style="width:100%; text-align:left; background:none; border:0; cursor:pointer;"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                        <form method="post" action="<?= esc(site_url('admin/packages/' . $fid . '/toggle-featured'), 'attr') ?>" style="margin:0;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="dropdown__item" role="menuitem" style="width:100%; text-align:left; background:none; border:0; cursor:pointer;"><?= $isFeatured ? 'Unfeature' : 'Make Featured' ?></button>
                                        </form>
                                        <button type="button" class="dropdown__item" role="menuitem" data-dropdown-item data-delete-trigger data-package-id="<?= esc((string)$fid, 'attr') ?>" data-package-name="<?= esc($pkg['name'], 'attr') ?>" style="color:var(--color-danger);">Delete Package</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Delete Modal -->
        <div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center; padding:16px;">
            <div class="card" style="max-width:480px; width:100%; padding:20px; display:grid; gap:16px;">
                <div>
                    <h3 style="margin:0; font-size:1.125rem;">Delete Package?</h3>
                    <p class="field__hint" style="margin:6px 0 0;">The package <strong id="deletePackageName"></strong> will be removed from the active listing. You can restore it later. This does not delete orders or files.</p>
                </div>
                <form method="post" id="deleteForm" style="margin:0;">
                    <?= csrf_field() ?>
                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button type="button" class="btn btn--ghost" id="btnDeleteCancel">Cancel</button>
                        <button type="submit" class="btn btn--primary" style="background:var(--color-danger); border-color:var(--color-danger);">Delete Package</button>
                    </div>
                </form>
            </div>
        </div>

        <?php
            $totalPages = (int) ceil($total / $perPage);
            $baseQuery = $queryParams;
        ?>
        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <div class="pagination__info" aria-live="polite">Page <?= esc((string)$currentPage) ?> of <?= esc((string)$totalPages) ?></div>
                <ul class="pagination__list">
                    <?php $prevPage=$currentPage-1; $nextPage=$currentPage+1; $buildUrl=function($page) use ($baseQuery){ $q=$baseQuery; if($page>1) $q['page']=$page; $qs=http_build_query($q); return site_url('admin/packages').($qs?'?'.$qs:''); }; ?>
                    <li><?php if($currentPage<=1): ?><span class="pagination__link pagination__link--disabled" aria-disabled="true">Previous</span><?php else: ?><a class="pagination__link" href="<?= esc($buildUrl($prevPage), 'attr') ?>" rel="prev">Previous</a><?php endif; ?></li>
                    <?php for($p=1;$p<=$totalPages;$p++): ?><?php if($p===$currentPage): ?><li><span class="pagination__link pagination__link--active" aria-current="page"><?= esc((string)$p) ?></span></li><?php else: ?><li><a class="pagination__link" href="<?= esc($buildUrl($p), 'attr') ?>"><?= esc((string)$p) ?></a></li><?php endif; ?><?php endfor; ?>
                    <li><?php if($currentPage>=$totalPages): ?><span class="pagination__link pagination__link--disabled" aria-disabled="true">Next</span><?php else: ?><a class="pagination__link" href="<?= esc($buildUrl($nextPage), 'attr') ?>" rel="next">Next</a><?php endif; ?></li>
                </ul>
            </nav>
        <?php else: ?>
            <div class="pagination"><div class="pagination__info">Page 1 of 1</div></div>
        <?php endif; ?>

        <script>
        (function(){
            // Delete modal
            const modal=document.getElementById('deleteModal');
            const deleteForm=document.getElementById('deleteForm');
            const deleteName=document.getElementById('deletePackageName');
            const btnCancel=document.getElementById('btnDeleteCancel');
            document.querySelectorAll('[data-delete-trigger]').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const id=btn.getAttribute('data-package-id');
                    const name=btn.getAttribute('data-package-name');
                    if(deleteName) deleteName.textContent=name;
                    if(deleteForm) deleteForm.action='<?= esc(site_url('admin/packages/'), 'attr') ?>'+id+'/delete';
                    if(modal){ modal.style.display='flex'; }
                });
            });
            if(btnCancel) btnCancel.addEventListener('click', ()=>{ if(modal) modal.style.display='none'; });
            if(modal) modal.addEventListener('click', (e)=>{ if(e.target===modal) modal.style.display='none'; });
            // Reorder
            const tbody=document.getElementById('packagesTbody');
            const btnSave=document.getElementById('btnSaveOrder');
            const reorderForm=document.getElementById('reorderForm');
            const inputOrdered=document.getElementById('orderedIdsInput');
            let originalOrder=[];
            let dirtyOrder=false;
            if(tbody) originalOrder=Array.from(tbody.querySelectorAll('tr')).map(tr=>tr.getAttribute('data-package-id'));
            function getCurrentOrder(){ return Array.from(tbody.querySelectorAll('tr')).map(tr=>tr.getAttribute('data-package-id')); }
            function checkDirty(){ const cur=getCurrentOrder(); dirtyOrder=JSON.stringify(cur)!==JSON.stringify(originalOrder); if(btnSave) btnSave.style.display=dirtyOrder?'inline-flex':'none'; }
            if(tbody && btnSave){
                tbody.addEventListener('click', (e)=>{
                    const up=e.target.closest('[data-move-up]');
                    const down=e.target.closest('[data-move-down]');
                    const tr=e.target.closest('tr');
                    if(!tr) return;
                    if(up){ const prev=tr.previousElementSibling; if(prev) tbody.insertBefore(tr,prev); checkDirty(); }
                    else if(down){ const next=tr.nextElementSibling; if(next) tbody.insertBefore(next,tr); else tbody.appendChild(tr); checkDirty(); }
                });
                let dragSrc=null;
                tbody.addEventListener('dragstart', (e)=>{
                    const tr=e.target.closest('tr');
                    if(!tr) return;
                    dragSrc=tr;
                    e.dataTransfer.effectAllowed='move';
                });
                tbody.addEventListener('dragover', (e)=>{
                    e.preventDefault();
                    const tr=e.target.closest('tr');
                    if(!tr||tr===dragSrc) return;
                    const rect=tr.getBoundingClientRect();
                    const mid=rect.top+rect.height/2;
                    if(e.clientY<mid) tbody.insertBefore(dragSrc,tr);
                    else tbody.insertBefore(dragSrc,tr.nextSibling);
                });
                tbody.addEventListener('drop', (e)=>{ e.preventDefault(); checkDirty(); });
                btnSave.addEventListener('click', ()=>{
                    const order=getCurrentOrder();
                    if(inputOrdered) inputOrdered.value=JSON.stringify(order);
                    if(reorderForm) reorderForm.submit();
                });
            }
        })();
        </script>

    <?php elseif ($isFilteredEmpty): ?>
        <div class="empty" role="status" aria-live="polite">
            <div class="empty__icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="6" stroke="currentColor" stroke-width="1.35"/><path d="M13 13l3 3" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/><path d="M8.5 10.5l1 1 2.5-2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <h3 class="empty__title">No matching packages</h3>
            <p class="empty__desc">Try changing your search or filters.</p>
            <a href="<?= esc(site_url('admin/packages'), 'attr') ?>" class="btn btn--secondary">Clear filters</a>
        </div>
    <?php elseif ($isDbEmpty): ?>
        <div class="empty" role="status" aria-live="polite">
            <div class="empty__icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 20 20" fill="none"><rect x="3" y="4" width="14" height="12" rx="1.4" stroke="currentColor" stroke-width="1.35"/><path d="M7 8h6M7 12h4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/></svg></div>
            <h3 class="empty__title">No packages yet</h3>
            <p class="empty__desc">Packages you create will appear here and can later be published on the Ftpreneur website.</p>
        </div>
    <?php endif; ?>

</div>

<?= $this->endSection() ?>
