<?php
require_once __DIR__ . '/includes/data.php';

$pageTitle = 'CoolOpz Portal | Client Job Update';
$token = trim((string) ($_REQUEST['token'] ?? ''));
$errorMessage = '';
$successMessage = '';
$job = null;
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
        $clientForm = [
            'site_address' => (string) ($job['site_address'] ?? ''),
            'google_maps_url' => (string) ($job['google_maps_url'] ?? ''),
            'person_in_charge_name' => (string) ($job['person_in_charge_name'] ?? ''),
            'person_in_charge_contact' => (string) ($job['person_in_charge_contact'] ?? ''),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $rawPersonInChargeContact = trim((string) ($_POST['person_in_charge_contact'] ?? ''));
            $clientForm = [
                'site_address' => trim((string) ($_POST['site_address'] ?? '')),
                'google_maps_url' => trim((string) ($_POST['google_maps_url'] ?? '')),
                'person_in_charge_name' => trim((string) ($_POST['person_in_charge_name'] ?? '')),
                'person_in_charge_contact' => coolopz_normalize_phone_number($rawPersonInChargeContact),
            ];

            if ($clientForm['google_maps_url'] !== '' && filter_var($clientForm['google_maps_url'], FILTER_VALIDATE_URL) === false) {
                $errorMessage = 'Please enter a valid Google Maps link.';
            } elseif (!coolopz_is_valid_phone_number($rawPersonInChargeContact)) {
                $errorMessage = 'Please enter a valid phone number for the PIC contact.';
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
                </div>
            </div>

            <form method="post" class="row g-3">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-12">
                    <label class="form-label" for="site_address">Site Address</label>
                    <textarea class="form-control notes-field" id="site_address" name="site_address" rows="3" placeholder="Enter the full service address"><?= htmlspecialchars($clientForm['site_address'], ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="google_maps_url">Google Maps Link</label>
                    <input class="form-control" id="google_maps_url" name="google_maps_url" type="url" value="<?= htmlspecialchars($clientForm['google_maps_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://maps.google.com/...">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="person_in_charge_name">Person In Charge Name</label>
                    <input class="form-control" id="person_in_charge_name" name="person_in_charge_name" type="text" value="<?= htmlspecialchars($clientForm['person_in_charge_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Contact person name">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="person_in_charge_contact">PIC Phone Number</label>
                    <input class="form-control" id="person_in_charge_contact" name="person_in_charge_contact" type="tel" inputmode="tel" value="<?= htmlspecialchars($clientForm['person_in_charge_contact'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Phone number only">
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
    });
    </script>
</body>
</html>