-- USERS
CREATE TABLE users (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    email_verified_at DATETIME,
    password VARCHAR(255)
);

-- TOKO
CREATE TABLE toko (
    id_toko INT AUTO_INCREMENT PRIMARY KEY,
    nama_toko VARCHAR(100) UNIQUE,
    biaya_admin DECIMAL(10,2),
    biaya_tambahan DECIMAL(10,2),
    marketplace ENUM('Shopee','Tiktok')
);

-- PRODUK
CREATE TABLE produk (
    sku VARCHAR(50) PRIMARY KEY,
    hpp DECIMAL(10,2)
);

-- IKLAN
CREATE TABLE iklan (
    no_pesanan VARCHAR(50) PRIMARY KEY,
    tanggal DATE,
    id_toko INT,
    jumlah_pembayaran DECIMAL(10,2),
    saldo DECIMAL(10,2),
    metode_pembayaran VARCHAR(50),
    FOREIGN KEY (id_toko) REFERENCES toko(id_toko)
);
CREATE TABLE pesanan (
    no_pesanan VARCHAR(50) PRIMARY KEY,
    tanggal DATE,
    no_resi VARCHAR(100),
    id_toko INT,
    id_user BIGINT,
    nama_pembeli VARCHAR(100),
    kurir VARCHAR(50),
    status ENUM('proses','kirim','selesai','return','pengembalian','batal') DEFAULT 'proses',
    total_hpp DECIMAL(10,2),
    total_harga DECIMAL(10,2),
    total_admin DECIMAL(10,2),
    pencairan DECIMAL(10,2),
    FOREIGN KEY (id_toko) REFERENCES toko(id_toko),
    FOREIGN KEY (id_user) REFERENCES users(id)
);

CREATE TABLE pesanan_per_produk (
    id_per_produk int primary auto increment
    no_pesanan VARCHAR(50),
    nama produk VARCHAR(255),
    variasi VARCHAR(100),
    jumlah INT,
    hpp DECIMAL(10,2),
    harga DECIMAL(10,2),
    FOREIGN KEY (no_pesanan) REFERENCES pesanan(no_pesanan),
);