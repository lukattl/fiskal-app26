<?php
$pageTitle = $pageTitle ?? 'fiskal-app';
$pageKey = $pageKey ?? '';
$fullName = $fullName ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
    <style>
        :root {
            --app-bg: #f4f7fb;
            --app-surface: rgba(255, 255, 255, 0.9);
            --app-border: #dbe4f0;
            --app-text: #17324d;
            --app-muted: #5f7288;
            --app-primary: #0d6efd;
            --app-primary-soft: #eaf2ff;
            --app-header-height: 3.2rem;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background:
                radial-gradient(circle at top left, rgba(13, 110, 253, 0.08), transparent 24rem),
                linear-gradient(180deg, #f8fbff 0%, var(--app-bg) 100%);
            color: var(--app-text);
            padding-top: var(--app-header-height);
        }

        .card {
            border: 1px solid var(--app-border);
            border-radius: .85rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .dashboard-grid {
            display: grid;
            grid-gap: 1rem;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        }

        .app-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1030;
            backdrop-filter: blur(16px);
            background: var(--app-surface);
            border-bottom: 1px solid rgba(219, 228, 240, 0.95);
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.06);
        }

        .app-brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            font-weight: 800;
            letter-spacing: .08em;
            color: var(--app-text);
            text-transform: uppercase;
        }

        .app-brand:hover,
        .app-brand:focus {
            color: var(--app-text);
        }

        .app-brand__mark {
            width: 2.25rem;
            height: 2.25rem;
            border-radius: .8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d6efd 0%, #5aa2ff 100%);
            color: #fff;
            font-size: .95rem;
            box-shadow: 0 12px 22px rgba(13, 110, 253, 0.24);
        }

        .app-nav-link {
            border-radius: 999px;
            color: var(--app-muted);
            font-weight: 600;
            padding: .45rem .9rem !important;
            transition: transform .2s ease, background-color .2s ease, color .2s ease, box-shadow .2s ease;
        }

        .app-nav-link:hover,
        .app-nav-link:focus {
            color: var(--app-text);
            background: #f2f6fc;
            transform: translateY(-1px);
        }

        .app-nav-link.active {
            color: var(--app-primary);
            background: var(--app-primary-soft);
            box-shadow: inset 0 0 0 1px rgba(13, 110, 253, 0.1);
        }

        .app-user-toggle {
            border-radius: 999px;
            border: 1px solid var(--app-border);
            background: #fff;
            color: var(--app-text);
            font-weight: 600;
            padding: .45rem 1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        .app-user-toggle:hover,
        .app-user-toggle:focus {
            background: #fff;
            color: var(--app-primary);
            border-color: #bfd2ec;
        }

        .app-user-menu .dropdown-menu {
            border-radius: 1rem;
            padding: .5rem;
            min-width: 12rem;
        }

        .app-user-menu .dropdown-item {
            border-radius: .7rem;
            font-weight: 600;
            padding: .55rem .8rem;
        }

        .app-user-menu .dropdown-item.active,
        .app-user-menu .dropdown-item:active {
            background: var(--app-primary-soft);
            color: var(--app-primary);
        }

        .app-user-name {
            max-width: 14rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: bottom;
        }

        @media (max-width: 1199.98px) {
            :root {
                --app-header-height: 3.5rem;
            }
        }

        @media (max-width: 1199.98px) {
            .app-nav-group {
                padding-top: 1rem;
                gap: .35rem;
            }

            .app-user-menu {
                padding-top: .75rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-xl app-header">
        <div class="container-fluid py-1 px-3 px-lg-4">
            <a class="navbar-brand app-brand me-4" href="dashboard.php">
                <span class="app-brand__mark">F26</span>
                <span>Fiskal 26</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appNavbar" aria-controls="appNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="appNavbar">
                <div class="navbar-nav ms-auto align-items-xl-center gap-xl-1 app-nav-group">
                    <a class="nav-link app-nav-link<?php echo $pageKey === 'dashboard' ? ' active' : ''; ?>" href="dashboard.php">Ploča</a>
                    <a class="nav-link app-nav-link<?php echo $pageKey === 'new-invoice' ? ' active' : ''; ?>" href="new-invoice.php">Novi račun</a>
                    <a class="nav-link app-nav-link<?php echo $pageKey === 'invoices' ? ' active' : ''; ?>" href="invoices.php">Računi</a>
                    <a class="nav-link app-nav-link<?php echo $pageKey === 'customers' ? ' active' : ''; ?>" href="customers.php">Kupci</a>
                    <div class="dropdown ms-xl-2 mt-3 mt-xl-0 app-user-menu">
                        <button class="btn btn-sm dropdown-toggle app-user-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="app-user-name"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                            <li><a class="dropdown-item<?php echo $pageKey === 'profile' ? ' active' : ''; ?>" href="my-profile.php">Moj profil</a></li>
                            <li><a class="dropdown-item<?php echo $pageKey === 'settings' ? ' active' : ''; ?>" href="settings.php">Postavke</a></li>
                            <li><hr class="dropdown-divider my-2"></li>
                            <li><button class="dropdown-item text-danger" type="button" data-logout data-logout-url="../api/logout.php">Odjava</button></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>
