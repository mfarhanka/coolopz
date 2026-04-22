<?php
require_once __DIR__ . '/includes/data.php';

$pageTitle = 'CoolOpz Portal | Client Job Update';
$token = trim((string) ($_REQUEST['token'] ?? ''));
$errorMessage = '';
$successMessage = '';
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
$defaultPersonInChargeName = '';
$defaultPersonInChargeContact = '';
$clientForm = [
    'site_address' => '',
    'google_maps_url' => '',
    'person_in_charge_name' => '',
    'person_in_charge_contact' => '',
];

if ($token === '') {
    $errorMessage = 'This update link is missing or invalid.';
} else {
    $job = coolopz_find_job_by_client_token($token);

    if ($job === null) {
        $errorMessage = 'This update link is no longer valid.';
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

        $defaultPersonInChargeName = trim((string) ($job['customer_name'] ?? ''));
        $defaultPersonInChargeContact = coolopz_normalize_phone_number((string) ($job['customer_phone_number'] ?? ''));

        $clientForm = [
            'site_address' => (string) ($job['site_address'] ?? ''),
            'google_maps_url' => (string) ($job['google_maps_url'] ?? ''),
            'person_in_charge_name' => trim((string) ($job['person_in_charge_name'] ?? '')) !== ''
                ? (string) $job['person_in_charge_name']
                : $defaultPersonInChargeName,
            'person_in_charge_contact' => trim((string) ($job['person_in_charge_contact'] ?? '')) !== ''
                ? (string) $job['person_in_charge_contact']
                : $defaultPersonInChargeContact,
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawPersonInChargeContact = trim((string) ($_POST['person_in_charge_contact'] ?? ''));
            $clientForm = [
                'site_address' => trim((string) ($_POST['site_address'] ?? '')),
                'google_maps_url' => trim((string) ($_POST['google_maps_url'] ?? '')),
                'person_in_charge_name' => trim((string) ($_POST['person_in_charge_name'] ?? '')),
                'person_in_charge_contact' => coolopz_normalize_phone_number($rawPersonInChargeContact),
            ];

            if ($clientForm['site_address'] === '' && $clientForm['google_maps_url'] === '') {
                $errorMessage = 'Please provide either the site address or a Google Maps link.';
            } elseif ($clientForm['google_maps_url'] !== '' && filter_var($clientForm['google_maps_url'], FILTER_VALIDATE_URL) === false) {
                $errorMessage = 'Please enter a valid Google Maps link.';
            } elseif (!coolopz_is_valid_phone_number($rawPersonInChargeContact)) {
                $errorMessage = 'Please enter a valid phone number for the Person In Charge contact.';
            } else {
                coolopz_update_job_client_details($token, $clientForm);
                $job = coolopz_find_job_by_client_token($token);
                $successMessage = 'Thanks. Your site details were updated successfully.';
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
            <span class="section-label">Client Update</span>
            <h1 class="panel-title mb-2">Share Site Details</h1>
            <p class="hero-copy mb-3">Use this form to confirm the site address, Google Maps link, and person-in-charge details for the job.</p>

<?php if ($errorMessage !== ''): ?>
            <div class="login-alert mb-3" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($successMessage !== ''): ?>
            <div class="form-success mb-3" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
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

            <div class="simple-panel mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                    <div>
                        <strong class="d-block">Services</strong>
                        <span class="subtle-note">Review the services included for this job.</span>
                    </div>
                    <div class="text-end">
                        <strong class="d-block">Total Cost</strong>
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
                        <span class="subtle-note">Before and after photos will appear here as the technician uploads them.</span>
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

            <form method="post" class="row g-3">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12">
                    <label class="form-label" for="site_address">Site Address</label>
                    <textarea class="form-control notes-field" id="site_address" name="site_address" rows="3" placeholder="Enter the full service address"><?= htmlspecialchars($clientForm['site_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="form-text">Add the site address here, or share a Google Maps link below.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="google_maps_url">Google Maps Link</label>
                    <input class="form-control" id="google_maps_url" name="google_maps_url" type="url" value="<?= htmlspecialchars($clientForm['google_maps_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://maps.google.com/...">
                    <div class="form-text">Optional if the site address is already filled in.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="person_in_charge_name">Person In Charge Name</label>
                    <input class="form-control" id="person_in_charge_name" name="person_in_charge_name" type="text" value="<?= htmlspecialchars($clientForm['person_in_charge_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Contact person name">
                    <div class="form-text">Defaulted to the customer name. Change it only if the Person In Charge is someone else.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="person_in_charge_contact">Person In Charge Phone Number</label>
                    <input class="form-control" id="person_in_charge_contact" name="person_in_charge_contact" type="tel" inputmode="tel" value="<?= htmlspecialchars($clientForm['person_in_charge_contact'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Phone number only">
                    <div class="form-text">Defaulted to the customer contact number. Update it only if the Person In Charge number is different from the customer's contact.</div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-portal-primary">Submit Details</button>
                </div>
            </form>
<?php endif; ?>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var picPhoneInput = document.getElementById('person_in_charge_contact');

        if (!(picPhoneInput instanceof HTMLInputElement)) {
            return;
        }

        var normalizePhone = function () {
            picPhoneInput.value = picPhoneInput.value.replace(/\D+/g, '');
        };

        picPhoneInput.addEventListener('input', normalizePhone);
        picPhoneInput.addEventListener('paste', function () {
            setTimeout(normalizePhone, 0);
        });

        normalizePhone();
    });
    </script>
</body>
</html>