CREATE TABLE parents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kpj VARCHAR(50),
  is_file BOOLEAN DEFAULT FALSE,
  file_path VARCHAR(255) DEFAULT NULL,
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

  notif_lasik ENUM('Aktif', 'jmo', 'cabang','not_found','15jt'),
  notif_eklp ENUM('aktif', 'tidak_aktif', 'not_found'),

  kota VARCHAR(100),
  kecamatan VARCHAR(100),
  kelurahan VARCHAR(100),

  percobaan_sipp INT,
  percobaan_lasik INT,
  percobaan_eklp INT,
  percobaan_dpt INT,

  sipp_status ENUM('error', 'pending', 'processing', 'success','not found'),
  lasik_status ENUM('error', 'pending', 'processing', 'success'),
  eklp_status ENUM('error', 'pending', 'processing', 'success'),
  dpt_status ENUM('error', 'pending', 'processing', 'success'),

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL
);

CREATE TABLE running_jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  end_at TIMESTAMP NULL,
  mode VARCHAR(50) NOT NULL,
  parent_id INT NULL,
  is_file BOOLEAN DEFAULT FALSE,
  status ENUM('pending', 'process', 'finish','stopped','error') DEFAULT 'pending',
  
  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL
);



-- Admin table
CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  last_login DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: admin123)
INSERT INTO admins (username, password) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

CREATE TABLE induk (
  id INT AUTO_INCREMENT PRIMARY KEY,
  induk VARCHAR(255) NOT NULL,
  is_selected BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE akun_sipp (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  is_selected BOOLEAN DEFAULT FALSE,
  tipe ENUM('sipp', 'eklp') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 插入 akun_sipp 的默认数据（示例）
INSERT INTO akun_sipp (email, password, is_selected, tipe) VALUES 
('pastibisa88v@gmail.com', 'Gondrong123', TRUE, 'sipp'),
('Sendiprayoga198@gmail.com', 'Bebek1997', FALSE, 'sipp'),
('rsudlawang@ymail.com', '123456', FALSE, 'eklp');
