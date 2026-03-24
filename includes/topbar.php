<?php
$currentUserName = $currentUserName ?? 'Admin User';
$currentUserRole = $currentUserRole ?? 'Operations Admin';
$userInitials = $userInitials ?? 'AU';
?>
            <header class="portal-topbar">
                <div>
                    <span class="topbar-label">CoolOpz Portal</span>
                    <p class="topbar-copy">Signed in as <?= htmlspecialchars($currentUserRole, ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <div class="topbar-actions">
                    <div class="user-chip">
                        <span class="user-avatar"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <strong><?= htmlspecialchars($currentUserName, ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= htmlspecialchars($currentUserRole, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-portal-secondary btn-sm">Logout</button>
                </div>
            </header>