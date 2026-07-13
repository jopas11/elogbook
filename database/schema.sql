CREATE DATABASE IF NOT EXISTS elogbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elogbook;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT INDEX ft_users (name, email) WITH PARSER ngram
);

CREATE TABLE IF NOT EXISTS jenis_spareparts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    type VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spareparts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('Aset','Non-Aset') NOT NULL,
    jenis_sparepart VARCHAR(255) NOT NULL,
    type_sparepart VARCHAR(255) DEFAULT NULL,
    serial_number VARCHAR(255) DEFAULT NULL UNIQUE,
    quantity INT NOT NULL DEFAULT 1,
    tanggal DATE NOT NULL,
    merk VARCHAR(255) DEFAULT NULL,
    pic VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    status ENUM('Tersedia','Terpakai','Rusak','Dalam Perbaikan') DEFAULT 'Tersedia',
    keterangan TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FULLTEXT INDEX ft_spareparts (jenis_sparepart, merk, type_sparepart, serial_number) WITH PARSER ngram
);

CREATE TABLE IF NOT EXISTS logbooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sparepart_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    tipe_transaksi ENUM('Barang Masuk','Barang Keluar','Ubah Status','Dalam Perbaikan','Permintaan') NOT NULL,
    pic_penerima VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    tanggal DATE NOT NULL,
    keterangan_log TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FULLTEXT INDEX ft_logbooks (pic_penerima) WITH PARSER ngram,
    FOREIGN KEY (sparepart_id) REFERENCES spareparts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Migration for existing databases (run if upgrading):
-- ALTER TABLE spareparts ADD FULLTEXT INDEX ft_spareparts (jenis_sparepart, merk, type_sparepart, serial_number) WITH PARSER ngram;
-- ALTER TABLE users ADD FULLTEXT INDEX ft_users (name, email) WITH PARSER ngram;
-- ALTER TABLE logbooks ADD FULLTEXT INDEX ft_logbooks (pic_penerima) WITH PARSER ngram;
