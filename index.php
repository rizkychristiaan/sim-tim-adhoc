<?php
/**
 * ==============================================================================
 * FILE: index.php (Root Entry Point)
 * SISTEM: Sistem Informasi Manajemen Tim Ad Hoc LPAIP UKDW
 * FUNGSI: Pintu masuk utama sistem ketika domain/URL root dibuka.
 * ALUR: Mengarahkan (redirect) seluruh lalu lintas awal ke folder /app/
 * ==============================================================================
 */
header("Location: app/");
exit();
?>
