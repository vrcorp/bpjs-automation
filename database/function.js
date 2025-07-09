// saver.js
import db from '../database/db.js';
import { session } from '../function/session.js';

export async function saveParent(parentResult) {

  // 先检查 kpj 是否已存在
  const checkQuery = `
    SELECT id FROM parents WHERE kpj = ?
  `;
  const [rows] = await db.execute(checkQuery, [parentResult.kpj]);

  if (rows.length > 0) {
    // 如果已存在，则更新 status，并返回 id
    const updateQuery = `
      UPDATE parents SET status = ? WHERE kpj = ?
    `;
    await db.execute(updateQuery, [parentResult.status, parentResult.kpj]);
    return rows[0].id;
  } else {
    // 如果不存在，则插入新数据
    const insertQuery = `
      INSERT INTO parents (kpj, status)
      VALUES (?, ?)
    `;
    const values = [
      parentResult.kpj,
      parentResult.status,
    ];
    const [result] = await db.execute(insertQuery, values);
    return result.insertId;
  }
}

export async function updateStatusParent(parentId, status){
  const query = `
    UPDATE parents
    SET status = ?
    WHERE id = ?
  `;
  const values = [status, parentId];
  await db.execute(query, values);
}

export async function checkParentStatus(kpj){
  // 根据给定的 kpj 查询 parents 表，返回对应的 status
  const query = `
    SELECT status FROM parents WHERE kpj = ?
  `;
  const [rows] = await db.execute(query, [kpj]);
  if (rows.length > 0) {
    return rows[0].status;
  } else {
    return null; // 未找到对应数据
  }
}

export async function saveChild(childResult, parentId) {
  // cari dulu apakah kpj sudah ada
  const [rows] = await db.execute(
    'SELECT id FROM result WHERE kpj = ?',
    [childResult.kpj]
  );

  let recordId; // <-- variabel penampung id

  if (rows.length) {
    // ====== UPDATE ======
    recordId = rows[0].id;              // ← id lama
    await db.execute(
      `UPDATE result SET
         parent_id = ?,
         nik = ?,
         nama = ?,
         ttl = ?,
         email = ?,
         hp = ?,
         notif_sipp = ?,
         percobaan_sipp = ?,
         sipp_status = ?
       WHERE id = ?`,                   // pakai id biar pasti unik
      [
        parentId,
        childResult.nik,
        childResult.nama_lengkap,
        `${childResult.tempat_lahir}, ${childResult.tgl_lahir}`,
        childResult.email,
        childResult.no_handphone,
        childResult.keterangan,
        session.attempt,
        childResult.sipp_status,
        recordId                         // ← di akhir values
      ]
    );
  } else {
    // ====== INSERT ======
    const [insertResult] = await db.execute(
      `INSERT INTO result (
         parent_id, nik, nama, kpj, ttl, email, hp,
         notif_sipp, percobaan_sipp, sipp_status
       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        parentId,
        childResult.nik,
        childResult.nama_lengkap,
        childResult.kpj,
        `${childResult.tempat_lahir}, ${childResult.tgl_lahir}`,
        childResult.email,
        childResult.no_handphone,
        childResult.keterangan,
        session.attempt,
        childResult.sipp_status
      ]
    );
    recordId = insertResult.insertId;   // ← id baru
  }

  return recordId;                      // ⬅️ pulangkan id-nya
}


export async function checkChildStatus(kpj){
    const query = `
    SELECT sipp_status FROM result WHERE kpj = ?
  `;
  const [rows] = await db.execute(query, [kpj]);
  if (rows.length > 0) {
    return rows[0].sipp_status;
  } else {
    return null; // 未找到对应数据
  }
}

export async function checkLasikStatus(kpj) {
  // 查询 result 表，返回指定 kpj 的 lasik_status
  const query = `
    SELECT lasik_status FROM result WHERE kpj = ?
  `;
  const [rows] = await db.execute(query, [kpj]);
  if (rows.length > 0) {
    return rows[0].lasik_status;
  } else {
    return null;
  }
}

export async function checkDPTStatus(nik, parentId) {
  // 查询 result 表，检查指定 nik 和 parentId 的 dpt_status
  const query = `
    SELECT dpt_status FROM result WHERE nik = ? AND parent_id = ?
  `;
  const [rows] = await db.execute(query, [nik, parentId]);
  if (rows.length > 0) {
    return rows[0].dpt_status;
  } else {
    return null; // 未找到对应数据
  }
}


export async function updateDPT(data, parentId) {
  
  const checkQuery = `
    SELECT id FROM result WHERE nik = ? AND parent_id = ?
  `;
  const [rows] = await db.execute(checkQuery, [data.nik, parentId]);
  if (rows.length > 0) {
    // 存在，执行 update
    const updateQuery = `
      UPDATE result
      SET
        kota = ?,
        kecamatan = ?,
        kelurahan = ?,
        percobaan_dpt = ?,
        dpt_status = ?
      WHERE nik = ? AND parent_id = ?
    `;
    const values = [
      data.kota,
      data.kecamatan,
      data.kelurahan,
      data.percobaan_dpt,
      data.dpt_status,
      data.nik,
      parentId
    ];
    await db.execute(updateQuery, values);
  }
  
}

export async function updateLasikStatus(childId, notifLasik, statusLasik){
  // Fungsi ini akan mengupdate data lasik/eklp pada tabel result berdasarkan id result
  const query = `
    UPDATE result
    SET
      notif_lasik = ?,
      percobaan_lasik = ?,
      lasik_status = ?
    WHERE id = ?
  `;

  const values = [
    notifLasik,     // 'Aktif', 'jmo', atau 'cabang'
    1, // INT
    statusLasik,    // 'error', 'pending', 'processing', 'success'
    childId                  // id pada tabel result
  ];

  await db.execute(query, values);
}

export async function updateEklpStatus(childId, notifEklp, statusEklp){
  // Fungsi ini akan mengupdate data lasik/eklp pada tabel result berdasarkan id result
  const query = `
    UPDATE result
    SET
      notif_eklp = ?,
      percobaan_eklp = ?,
      eklp_status = ?
    WHERE id = ?
  `;

  const values = [
    notifEklp,     // 'Aktif', 'jmo', atau 'cabang'
    1, // INT
    statusEklp,    // 'error', 'pending', 'processing', 'success'
    childId                  // id pada tabel result
  ];

  await db.execute(query, values);
}

export async function saveDpt(dptResult) {
  // Fungsi ini akan mengupdate data DPT pada tabel result berdasarkan id result
  const query = `
    UPDATE result
    SET
      kota = ?,
      kecamatan = ?,
      kelurahan = ?,
      percobaan_dpt = ?,
      dpt_status = ?
    WHERE id = ?
  `;

  const values = [
    dptResult.kota,            // Nama kota (string)
    dptResult.kecamatan,       // Nama kecamatan (string)
    dptResult.kelurahan,       // Nama kelurahan (string)
    dptResult.percobaan_dpt,   // INT
    dptResult.dpt_status,      // 'error', 'pending', 'processing', 'success'
    dptResult.id               // id pada tabel result
  ];

  await db.execute(query, values);
}

export async function checkEklpStatus(kpj) {
  // 查询 result 表，返回指定 kpj 的 eklp_status
  const query = `
    SELECT eklp_status FROM result WHERE kpj = ?
  `;
  const [rows] = await db.execute(query, [kpj]);
  if (rows.length > 0) {
    return rows[0].eklp_status;
  } else {
    return null;
  }
}

export async function getSelectedInduk() {
  // 查询 induk 表，获取 is_selected = 1 的记录
  const query = `
    SELECT * FROM induk WHERE is_selected = 1 LIMIT 1
  `;
  const [rows] = await db.execute(query);
  if (rows.length > 0) {
    return rows[0];
  } else {
    return '1101743';
  }
}


export async function getParentById(parentId,is_file = false) {
  // 查询 parents 表，返回指定 id 的 parent 记录
  let query, params;
  if (is_file) {
    query = `
      SELECT * FROM parents WHERE id = ? AND is_file = TRUE
    `;
    params = [parentId];
  } else {
    query = `
      SELECT * FROM parents WHERE id = ?
    `;
    params = [parentId];
  }
  const [rows] = await db.execute(query, params);
  if (rows.length > 0) {
    return rows[0];
  } else {
    return null;
  }
}

export async function getAllFileParents() {
  // 查询 parents 表，返回所有 is_file = TRUE 的记录
  const query = `
    SELECT * FROM parents WHERE is_file = TRUE
  `;
  const [rows] = await db.execute(query);
  return rows;
}


export async function getChildrenByParentId(parentId) {
  // 查询 result 表，返回指定 parent_id 的所有子项
  const query = `
    SELECT * FROM result WHERE parent_id = ?
  `;
  const [rows] = await db.execute(query, [parentId]);
  return rows;
}




