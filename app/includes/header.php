<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . " - SIM Tim Ad Hoc LPAIP" : "SIM Tim Ad Hoc LPAIP" ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Select2 Searchable Dropdown CSS & Bootstrap 5 Theme -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    
    <!-- CSS Utama Tema UKDW (Terpisah) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">

    <!-- Auto-Load CSS Spesifik Halaman (Jika ada file CSS dengan nama yang sama) -->
    <?php 
    $current_page_name = basename($_SERVER['PHP_SELF'], ".php");
    $custom_page_css = __DIR__ . "/../assets/css/" . $current_page_name . ".css";
    if (file_exists($custom_page_css)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/<?= $current_page_name ?>.css">
    <?php endif; ?>
</head>
<body>
