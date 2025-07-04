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

export async function saveChild(childResult,parentId) {
  // 先检查 result 表中是否已存在对应的 kpj，如果有则 update，否则 insert
  const checkQuery = `
    SELECT id FROM result WHERE kpj = ?
  `;
  const [rows] = await db.execute(checkQuery, [childResult.kpj]);

  if (rows.length > 0) {
    // 已存在，执行 update
    const updateQuery = `
      UPDATE result SET
        parent_id = ?,
        nik = ?,
        nama = ?,
        ttl = ?,
        email = ?,
        hp = ?,
        notif_sipp = ?,
        percobaan_sipp = ?,
        sipp_status = ?
      WHERE kpj = ?
    `;
    const values = [
      parentId,
      childResult.nik,
      childResult.nama_lengkap,
      `${childResult.tempat_lahir}, ${childResult.tgl_lahir}`,
      childResult.email,
      childResult.no_handphone,
      childResult.keterangan,
      session.attempt,
      childResult.sipp_status,
      childResult.kpj
    ];
    await db.execute(updateQuery, values);
  } else {
    // 不存在，执行 insert
    const insertQuery = `
      INSERT INTO result (
        parent_id,
        nik,
        nama,
        kpj,
        ttl,
        email,
        hp,
        notif_sipp,
        percobaan_sipp,
        sipp_status
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    const values = [
      parentId,
      childResult.nik,
      childResult.nama_lengkap,
      childResult.kpj,
      `${childResult.tempat_lahir}, ${childResult.tgl_lahir}`,
      childResult.email,
      childResult.no_handphone,
      childResult.keterangan,
      session.attempt,
      childResult.sipp_status,
    ];
    await db.execute(insertQuery, values);
  }
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

export async function saveLasik(lasikResult) {
  // Fungsi ini akan mengupdate data lasik/eklp pada tabel result berdasarkan id result
  const query = `
    UPDATE result
    SET
      jenis = ?,
      notif_lasik_eklp = ?,
      percobaan_lasik_eklp = ?,
      lasik_eklp_status = ?
    WHERE id = ?
  `;

  const values = [
    lasikResult.jenis,                // 'lasik' atau 'eklp'
    lasikResult.notif_lasik_eklp,     // 'Aktif', 'jmo', atau 'cabang'
    lasikResult.percobaan_lasik_eklp, // INT
    lasikResult.lasik_eklp_status,    // 'error', 'pending', 'processing', 'success'
    lasikResult.id                    // id pada tabel result
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

