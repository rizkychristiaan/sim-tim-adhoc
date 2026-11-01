-- ===================================================================
-- DATABASE SCHEME FOR SISTEM MANAJEMEN TIM AD HOC LPAIP
-- Skripsi: Rancang Bangun Sistem Manajemen Tim Ad Hoc Berbasis Web
-- ===================================================================

CREATE DATABASE IF NOT EXISTS `db_sim_adhoc` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_sim_adhoc`;

-- 1. TABEL USERS (Pengguna Sistem)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nip_nim` VARCHAR(50) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Aktif, 0 = Pending Approval Admin',
  `role` ENUM('admin', 'ketua', 'anggota') NOT NULL DEFAULT 'anggota',
  `jabatan_struktural` VARCHAR(100) DEFAULT 'Staf/Dosen LPAIP',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Awal User (Password default hashed using password_hash 'admin123' / 'user123')
INSERT INTO `users` (`id`, `nip_nim`, `nama`, `email`, `password`, `role`, `jabatan_struktural`) VALUES
(1, 'P001', 'Admin LPAIP Utama', 'admin@lpaip.ukdw.ac.id', '$2y$10$1ovwQ.JNKeI.OWSuaKmsbeM9b9bjyu.Rhua2B/sWrNS46RntmNZvm', 'admin', 'Kepala Sekretariat LPAIP'),
(2, 'D001', 'Dr. Antonius Rachmat C., S.Kom., M.Cs.', 'antonius@lpaip.ukdw.ac.id', '$2y$10$.Uxm7HBseM1QNkuDEHB2/e6hSWg3E2A.cYeiQUchkvlPD05A0JLFW', 'ketua', 'Dosen Pembimbing / Ketua Tim'),
(3, '71220902', 'Rizky Chrales Christiaan', 'rizky.chrales@ti.ukdw.ac.id', '$2y$10$.Uxm7HBseM1QNkuDEHB2/e6hSWg3E2A.cYeiQUchkvlPD05A0JLFW', 'anggota', 'Anggota Tim Ad Hoc'),
(4, 'D002', 'Drs. Budi Setyawan, M.T.', 'budi@lpaip.ukdw.ac.id', '$2y$10$.Uxm7HBseM1QNkuDEHB2/e6hSWg3E2A.cYeiQUchkvlPD05A0JLFW', 'ketua', 'Dosen LPAIP'),
(5, 'S001', 'Siti Rahmawati, A.Md.', 'siti@lpaip.ukdw.ac.id', '$2y$10$.Uxm7HBseM1QNkuDEHB2/e6hSWg3E2A.cYeiQUchkvlPD05A0JLFW', 'anggota', 'Staf Administrasi LPAIP');

-- 2. TABEL TIM AD HOC
DROP TABLE IF EXISTS `tim_adhoc`;
CREATE TABLE `tim_adhoc` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nomor_sk` VARCHAR(100) NOT NULL,
  `nama_tim` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `tanggal_mulai` DATE NOT NULL,
  `tanggal_selesai` DATE NOT NULL,
  `status` ENUM('Perencanaan', 'Aktif', 'Selesai', 'Diperpanjang') NOT NULL DEFAULT 'Aktif',
  `created_by` INT(11) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Contoh Tim Ad Hoc
INSERT INTO `tim_adhoc` (`id`, `nomor_sk`, `nama_tim`, `deskripsi`, `tanggal_mulai`, `tanggal_selesai`, `status`, `created_by`) VALUES
(1, 'SK/LPAIP/2026/001', 'Panitia Akreditasi Program Studi 2026', 'Tim khusus bertugas menyiapkan dokumen instrumen akreditasi prodi di lingkungan LPAIP.', '2026-03-01', '2026-08-31', 'Aktif', 1),
(2, 'SK/LPAIP/2026/002', 'Tim Pengembang Kurikulum Berbasis AI', 'Tim perencana penyelarasan kurikulum berbasis kecerdasan buatan.', '2026-04-15', '2026-10-15', 'Aktif', 1);

-- 3. TABEL ANGGOTA TIM
DROP TABLE IF EXISTS `anggota_tim`;
CREATE TABLE `anggota_tim` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tim_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `peran_tim` ENUM('Ketua', 'Sekretaris', 'Bendahara', 'Anggota') NOT NULL DEFAULT 'Anggota',
  `tgl_bergabung` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tim_id`) REFERENCES `tim_adhoc` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Contoh Anggota Tim
INSERT INTO `anggota_tim` (`id`, `tim_id`, `user_id`, `peran_tim`) VALUES
(1, 1, 2, 'Ketua'),
(2, 1, 3, 'Sekretaris'),
(3, 1, 5, 'Anggota'),
(4, 2, 4, 'Ketua'),
(5, 2, 3, 'Anggota');

-- 4. TABEL TUGAS (Task Management)
DROP TABLE IF EXISTS `tugas`;
CREATE TABLE `tugas` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tim_id` INT(11) NOT NULL,
  `assigned_to` INT(11) NOT NULL,
  `judul_tugas` VARCHAR(200) NOT NULL,
  `deskripsi_tugas` TEXT DEFAULT NULL,
  `prioritas` ENUM('Rendah', 'Sedang', 'Tinggi', 'Mendesak') NOT NULL DEFAULT 'Sedang',
  `status` ENUM('Belum Dikerjakan', 'Sedang Dikerjakan', 'Menunggu Review', 'Selesai') NOT NULL DEFAULT 'Belum Dikerjakan',
  `deadline` DATE NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tim_id`) REFERENCES `tim_adhoc` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Data Contoh Penugasan
INSERT INTO `tugas` (`id`, `tim_id`, `assigned_to`, `judul_tugas`, `deskripsi_tugas`, `prioritas`, `status`, `deadline`) VALUES
(1, 1, 3, 'Menyusun Borang Standard 3 & 4', 'Mengumpulkan data pendukung kualifikasi dosen dan fasilitas laboratorium.', 'Tinggi', 'Sedang Dikerjakan', '2026-08-10'),
(2, 1, 5, 'Mengunggah Berkas Pendukung LPJ', 'Kumpulkan seluruh kwitansi dan berita acara rapat tim.', 'Sedang', 'Belum Dikerjakan', '2026-08-15'),
(3, 2, 3, 'Analisis Kebutuhan Matakuliah Machine Learning', 'Riset silabus perguruan tinggi acuan.', 'Mendesak', 'Menunggu Review', '2026-08-05');

-- 5. TABEL DOKUMEN (Repository Berkas)
DROP TABLE IF EXISTS `dokumen`;
CREATE TABLE `dokumen` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tim_id` INT(11) NOT NULL,
  `uploader_id` INT(11) NOT NULL,
  `nama_dokumen` VARCHAR(200) NOT NULL,
  `jenis_dokumen` ENUM('Proposal', 'SK Penugasan', 'LPJ', 'Berita Acara', 'Lainnya') NOT NULL DEFAULT 'Lainnya',
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tim_id`) REFERENCES `tim_adhoc` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5b. TABEL TUGAS_FILES (Lampiran Berkas Pengerjaan Tugas Multi-File)
DROP TABLE IF EXISTS `tugas_files`;
CREATE TABLE `tugas_files` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tugas_id` INT(11) NOT NULL,
  `uploader_id` INT(11) NOT NULL,
  `nama_file_asli` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT(11) DEFAULT 0,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. TABEL NOTIFIKASI
DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `notifikasi` (`user_id`, `judul`, `pesan`) VALUES
(3, 'Penugasan Baru', 'Anda ditugaskan pada tugas: Menyusun Borang Standard 3 & 4 dalam Tim Akreditasi 2026.'),
(3, 'Pengingat Deadline', 'Tugas Analisis Kebutuhan Matakuliah Machine Learning mendekati batas waktu (05 Agustus 2026).');
