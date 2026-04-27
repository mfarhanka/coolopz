<?php
require_once __DIR__ . '/includes/data.php';

$pageTitle = 'CoolOpz Portal | Service Report';
$token = trim((string) ($_REQUEST['token'] ?? ''));
$errorMessage = '';
$job = null;
$serviceLines = [];
$serviceTotal = 0.0;
$jobReportPhotoMap = [
    'services' => [],
    'gas_meter' => [
        'before' => null,
        'after' => null,
    ],
];

if ($token === '') {
    $errorMessage = 'This service report link is missing or invalid.';
} else {
    $job = coolopz_find_job_by_client_token($token);

    if ($job === null) {
        $errorMessage = 'This service report link is no longer valid.';
    } else {
        $serviceLines = coolopz_fetch_job_service_lines((int) $job['id']);
        $jobReportPhotoMap = coolopz_job_report_photo_map((int) $job['id']);
        $serviceTotal = $serviceLines !== []
            ? coolopz_calculate_billed_amount($serviceLines)
            : (float) ($job['billed_amount'] ?? 0);

        if ($serviceLines === [] && trim((string) ($job['service_type'] ?? '')) !== '') {
            foreach (coolopz_normalize_service_names(explode(',', (string) $job['service_type'])) as $serviceName) {
                $serviceLines[] = [
                    'service_name' => $serviceName,
                    'line_price' => null,
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body class="public-page">
    <main class="public-shell">
        <section class="public-card simple-panel">
            <span class="section-label">Service Report</span>
            <h1 class="panel-title mb-2">Service Report and Payment</h1>
            <p class="hero-copy mb-3">Review the completed service items, technician report photos, and total billed amount for this job.</p>

<?php if ($errorMessage !== ''): ?>
            <div class="login-alert mb-3" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($job !== null): ?>
            <div class="public-meta mb-3">
                <div>
                    <strong class="d-block"><?= htmlspecialchars($job['ticket_number'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($job['service_type'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div>
                    <strong class="d-block">Customer</strong>
                    <span><?= htmlspecialchars($job['customer_name'] !== '' ? $job['customer_name'] : 'Not assigned yet', ENT_QUOTES, 'UTF-8') ?></span>
<?php if (($job['customer_phone_number'] ?? '') !== ''): ?>
                    <span class="subtle-note d-block"><?= htmlspecialchars((string) $job['customer_phone_number'], ENT_QUOTES, 'UTF-8') ?></span>
<?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap mb-3">
                <a class="btn btn-outline-primary" href="<?= htmlspecialchars(coolopz_job_client_update_url((string) $job['client_update_token']), ENT_QUOTES, 'UTF-8') ?>">Update Site Details</a>
            </div>

            <div class="simple-panel mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                    <div>
                        <strong class="d-block">Services</strong>
                        <span class="subtle-note">Service items billed for this job.</span>
                    </div>
                    <div class="text-end">
                        <strong class="d-block">Total Payment</strong>
                        <span><?= htmlspecialchars('RM' . number_format($serviceTotal, 2), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
<?php if ($serviceLines !== []): ?>
                <div class="table-responsive">
                    <table class="table portal-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th class="text-end">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach ($serviceLines as $serviceLine): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) $serviceLine['service_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end"><?= htmlspecialchars($serviceLine['line_price'] === null ? '-' : 'RM' . number_format((float) $serviceLine['line_price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
<?php else: ?>
                <p class="subtle-note mb-0">No services have been added to this job yet.</p>
<?php endif; ?>
            </div>

            <div class="simple-panel mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                    <div>
                        <strong class="d-block">Technician Photo Report</strong>
                        <span class="subtle-note">Before and after photos uploaded by the technician.</span>
                    </div>
                </div>
<?php if ($serviceLines !== []): ?>
                <div class="row g-3">
<?php foreach ($serviceLines as $serviceLine): ?>
<?php $serviceName = (string) $serviceLine['service_name']; ?>
<?php $servicePhotos = $jobReportPhotoMap['services'][$serviceName] ?? ['before' => null, 'after' => null]; ?>
                    <div class="col-12">
                        <div class="simple-panel">
                            <strong class="d-block mb-2"><?= htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') ?></strong>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <span class="subtle-note d-block mb-2">Before</span>
<?php if (($servicePhotos['before']['file_path'] ?? '') !== ''): ?>
                                    <a href="<?= htmlspecialchars((string) $servicePhotos['before']['file_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                        <img class="img-fluid rounded border" src="<?= htmlspecialchars((string) $servicePhotos['before']['file_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($serviceName . ' before photo', ENT_QUOTES, 'UTF-8') ?>" style="max-height: 180px; object-fit: cover;">
                                    </a>
<?php else: ?>
                                    <div class="subtle-note">No before photo uploaded yet.</div>
<?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <span class="subtle-note d-block mb-2">After</span>
<?php if (($servicePhotos['after']['file_path'] ?? '') !== ''): ?>
                                    <a href="<?= htmlspecialchars((string) $servicePhotos['after']['file_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                        <img class="img-fluid rounded border" src="<?= htmlspecialchars((string) $servicePhotos['after']['file_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($serviceName . ' after photo', ENT_QUOTES, 'UTF-8') ?>" style="max-height: 180px; object-fit: cover;">
                                    </a>
<?php else: ?>
                                    <div class="subtle-note">No after photo uploaded yet.</div>
<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
<?php endif; ?>

                <div class="simple-panel mt-3">
                    <strong class="d-block mb-2">Gas Refill Meter Report</strong>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="subtle-note d-block mb-2">Meter Before</span>
<?php if (($jobReportPhotoMap['gas_meter']['before']['file_path'] ?? '') !== ''): ?>
                            <a href="<?= htmlspecialchars((string) $jobReportPhotoMap['gas_meter']['before']['file_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <img class="img-fluid rounded border" src="<?= htmlspecialchars((string) $jobReportPhotoMap['gas_meter']['before']['file_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Gas meter before photo" style="max-height: 180px; object-fit: cover;">
                            </a>
<?php else: ?>
                            <div class="subtle-note">No meter before photo uploaded yet.</div>
<?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <span class="subtle-note d-block mb-2">Meter After</span>
<?php if (($jobReportPhotoMap['gas_meter']['after']['file_path'] ?? '') !== ''): ?>
                            <a href="<?= htmlspecialchars((string) $jobReportPhotoMap['gas_meter']['after']['file_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                <img class="img-fluid rounded border" src="<?= htmlspecialchars((string) $jobReportPhotoMap['gas_meter']['after']['file_path'], ENT_QUOTES, 'UTF-8') ?>" alt="Gas meter after photo" style="max-height: 180px; object-fit: cover;">
                            </a>
<?php else: ?>
                            <div class="subtle-note">No meter after photo uploaded yet.</div>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
<?php endif; ?>
        </section>
    </main>
</body>
</html>