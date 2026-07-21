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
    kategori ENUM('Aset','Non-Aset') DEFAULT NULL,
    nama VARCHAR(255) NOT NULL,
    type VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS spareparts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    kategori ENUM('Aset','Non-Aset') NOT NULL,
    jenis_penggunaan ENUM('Reusable','Consumable') DEFAULT NULL,
    lokasi_penyimpanan VARCHAR(255) DEFAULT NULL,
    minimum_stok INT NOT NULL DEFAULT 1,
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
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FULLTEXT INDEX ft_spareparts (jenis_sparepart, merk, type_sparepart, serial_number) WITH PARSER ngram
);

CREATE TABLE IF NOT EXISTS logbooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sparepart_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    tipe_transaksi ENUM('Barang Masuk','Barang Keluar','Ubah Status','Dalam Perbaikan','Permintaan','Dihapus','Dipinjam','Dikembalikan') NOT NULL,
    status_lama VARCHAR(50) DEFAULT NULL,
    status_baru VARCHAR(50) DEFAULT NULL,
    pic_penerima VARCHAR(255) DEFAULT NULL,
    department VARCHAR(255) DEFAULT NULL,
    tanggal DATE NOT NULL,
    keterangan_log TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FULLTEXT INDEX ft_logbooks_pic (pic_penerima) WITH PARSER ngram,
    FOREIGN KEY (sparepart_id) REFERENCES spareparts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS peminjaman (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sparepart_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    peminjam VARCHAR(255) NOT NULL,
    department VARCHAR(255) DEFAULT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_rencana_kembali DATE DEFAULT NULL,
    kondisi_pinjam TEXT DEFAULT NULL,
    keterangan TEXT DEFAULT NULL,
    status ENUM('Dipinjam','Telat','Dikembalikan') DEFAULT 'Dipinjam',
    tanggal_kembali DATE DEFAULT NULL,
    kondisi_kembali TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (sparepart_id) REFERENCES spareparts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
