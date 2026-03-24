<?php
$activePage = $activePage ?? 'dashboard';

$navItems = [
    [
        'key' => 'dashboard',
        'href' => 'index.php',
        'label' => 'Dashboard',
        'icon' => '<path d="M3 11.5 12 4l9 7.5"></path><path d="M5 10.5V20h14v-9.5"></path>',
    ],
    [
        'key' => 'jobs',
        'href' => 'jobs.php',
        'label' => 'Jobs',
        'icon' => '<rect x="4" y="5" width="16" height="14" rx="2"></rect><path d="M8 9h8"></path><path d="M8 13h8"></path>',
    ],
    [
        'key' => 'customers',
        'href' => 'customers.php',
        'label' => 'Customers',
        'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path><circle cx="9.5" cy="7" r="3"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 4.13a4 4 0 0 1 0 7.75"></path>',
    ],
    [
        'key' => 'reports',
        'href' => 'reports.php',
        'label' => 'Reports',
        'icon' => '<path d="M4 19h16"></path><path d="M7 16V9"></path><path d="M12 16V5"></path><path d="M17 16v-4"></path>',
    ],
];
?>
        <aside class="portal-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="portalSidebar" aria-labelledby="portalSidebarLabel">
            <div class="offcanvas-header portal-offcanvas-header d-lg-none">
                <a class="sidebar-brand" href="index.php" id="portalSidebarLabel">
                    <span class="brand-mark">CO</span>
                    <span>
                        <span class="brand-title d-block">CoolOpz Portal</span>
                        <span class="brand-subtitle d-block">Aircond Service Management</span>
                    </span>
                </a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#portalSidebar" aria-label="Close"></button>
            </div>

            <div class="offcanvas-body portal-sidebar-body">
                <div class="w-100">
                    <a class="sidebar-brand d-none d-lg-flex" href="index.php">
                        <span class="brand-mark">CO</span>
                        <span>
                            <span class="brand-title d-block">CoolOpz Portal</span>
                            <span class="brand-subtitle d-block">Aircond Service Management</span>
                        </span>
                    </a>

                    <nav class="sidebar-nav mt-4 mt-lg-4">
<?php foreach ($navItems as $item): ?>
                    <a class="sidebar-link<?= $activePage === $item['key'] ? ' active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="sidebar-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <?= $item['icon'] ?>
                            </svg>
                        </span>
                        <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
<?php endforeach; ?>
                    </nav>
                </div>
            </div>
        </aside>