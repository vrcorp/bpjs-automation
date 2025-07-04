CREATE TABLE parents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kpj VARCHAR(50),
  status ENUM('error', 'pending', 'processing', 'success','not found'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE result (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT,
  nik VARCHAR(50),
  nama VARCHAR(100),
  kpj VARCHAR(50),
  ttl VARCHAR(100), -- Tempat Tanggal Lahir
  email VARCHAR(100),
  hp VARCHAR(20),
  notif_sipp ENUM('Sukses','Tidak bisa digunakan','Not Found','Not Found Modal','ERROR'),
  jenis ENUM('lasik', 'eklp'),
  notif_lasik_eklp ENUM('Aktif', 'jmo', 'cabang'),
  kota VARCHAR(100),
  kecamatan VARCHAR(100),
  kelurahan VARCHAR(100),
  percobaan_sipp INT,
  percobaan_lasik_eklp INT,
  percobaan_dpt INT,
  sipp_status ENUM('error', 'pending', 'processing', 'success','not found'),
  lasik_eklp_status ENUM('error', 'pending', 'processing', 'success'),
  dpt_status ENUM('error', 'pending', 'processing', 'success'),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL
);
