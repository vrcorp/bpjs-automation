import express from "express";
import {resumeChild} from "./function/resumeChild_sipp.js";
import {generateSipp} from "./function/generateSipp.js";
import {runParentById} from "./function/resumeParent_sipp.js";
import {scrapeDpt} from "./function/dptScrape.js";
import {scrapeLasik} from "./function/lasikScrape.js";
import {scrapeEklp} from "./function/eklpScraper.js";
import { generateAll } from "./function/generateAll.js";
import bodyParser from "body-parser";
import ExcelJS from 'exceljs';
import db from './database/db.js';
import cors from 'cors';

const app = express();
app.use(express.json());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(cors({
  origin: 'http://localhost',          // front-end dev server
  methods: ['GET','POST','PUT','DELETE','OPTIONS'],
  allowedHeaders: ['Content-Type','Authorization'],
  credentials: true                    // kalau butuh cookie / auth header
}));

/* ───── SIPP Routes ───── */
app.post("/generate", async (req, res) => {
  const mode   = req.body.mode   ?? "default";
  const file   = req.body.file   ?? null;
  const action = req.body.action ?? "start";
  try {
    generateSipp({ mode, file, action })
    .then(() => console.log("✅ generateSipp selesai"))
    .catch(err => console.error("❌ generateSipp error:", err));

    res.status(202).json({ status: "OK", message: "Job diterima & lagi diproses" });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.post('/generate/stop', async (_, res) => {
  await generateSipp({ action: 'stop' });
  res.json({ status: 'OK', msg: 'tab generate ditutup' });
});

/* ───── Parent Routes ───── */
app.post("/resume-parent/:id", async (req, res) => {
  runParentById({ parentId: req.params.id });
  res.status(202).json({ status: 'OK', msg: 'parent resume jalan' });
});

app.post('/resume-parent/:id/stop', async (req, res) => {
  await runParentById({ parentId: req.params.id, action: 'stop' });
  res.json({ status: 'OK', msg: 'tab parent ditutup' });
});

/* ───── Child Routes ───── */
app.post('/resume-child/:id', async (req, res) => {
  resumeChild({ childId: req.params.id });
  res.status(202).json({ status: 'OK', msg: 'child resume jalan' });
});

app.post('/resume-child/:id/stop', async (req, res) => {
  await resumeChild({ childId: req.params.id, action: 'stop' });
  res.json({ status: 'OK', msg: 'tab child ditutup' });
});

/* ───── DPT Routes ───── */
app.post('/dpt-scrape', async (req, res) => {
  try {
    const { data, action = 'start', type = "child" } = req.body;
    scrapeDpt({ data, action, type })
      .then(result => console.log("✅ DPT scrape selesai:", result))
      .catch(err => console.error("❌ DPT scrape error:", err));
    
    res.status(202).json({ status: "OK", message: "DPT scraping started" });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.post('/dpt-scrape/stop', async (req, res) => {
  const { type = "child" } = req.body;
  await scrapeDpt({ action: 'stop', type });
  res.json({ status: 'OK', msg: `DPT ${type} scraping stopped` });
});

/* ───── LASIK Routes ───── */
app.post('/lasik-scrape', async (req, res) => {
  try {
    const { data, action = 'start', type = "child" } = req.body;
    scrapeLasik({ data, action, type })
      .then(result => console.log("✅ LASIK scrape selesai:", result))
      .catch(err => console.error("❌ LASIK scrape error:", err));
    
    res.status(202).json({ status: "OK", message: "LASIK scraping started" });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.post('/lasik-scrape/stop', async (req, res) => {
  const { type = "child" } = req.body;
  await scrapeLasik({ action: 'stop', type });
  res.json({ status: 'OK', msg: `LASIK ${type} scraping stopped` });
});

/* ───── EKLP Routes ───── */
app.post('/eklp-scrape', async (req, res) => {
  try {
    const { data, action = 'start', type = "child" } = req.body;
    scrapeEklp({ data, action, type })
      .then(result => console.log("✅ EKLP scrape selesai:", result))
      .catch(err => console.error("❌ EKLP scrape error:", err));
    
    res.status(202).json({ status: "OK", message: "EKLP scraping started" });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.post('/eklp-scrape/stop', async (req, res) => {
  const { type = "child" } = req.body;
  await scrapeEklp({ action: 'stop', type });
  res.json({ status: 'OK', msg: `EKLP ${type} scraping stopped` });
});

app.post("/generate-all", async (req, res) => {
  const mode = req.body.mode ?? "sipp_lasik_dpt";
  const parentId = req.body.parentId ?? null;
  const is_file = req.body.is_file ?? false;
  try {
    generateAll({ mode, parentId, is_file })
      .then(() => console.log("✅ generateAll selesai"))
      .catch((err) => console.error("❌ generateAll error:", err));
    res.status(202).json({ status: "OK", message: "Job generate-all diterima & diproses" });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.post('/export-all', async (req, res) => {
  const mode = req.body.mode || 'sipp_lasik_dpt';
  const parentId = req.body.parentId || null;
  const is_file = req.body.is_file ?? false;
  console.log(parentId, mode, is_file);
  let columns = [
    { header: 'NIK', key: 'nik' },
    { header: 'KPJ', key: 'kpj' },
    { header: 'Nama', key: 'nama' },
    { header: 'Kota', key: 'kota' },
    { header: 'Kecamatan', key: 'kecamatan' },
    { header: 'Kelurahan', key: 'kelurahan' }
  ];
  if (mode === 'sipp_lasik_dpt') columns.push({ header: 'Lasik', key: 'notif_lasik' });
  if (mode === 'sipp_eklp_dpt') columns.push({ header: 'EKLP', key: 'notif_eklp' });

  // 查询 result 表，筛选 kpj 和 nama 不为空
  // 如果 parentId 不为 null，则加上 parent_id 的筛选条件
  // 根据 is_file 参数，筛选 parent 的 is_file 字段
  let query = `
    SELECT r.nik, r.kpj, r.nama, r.kota, r.kecamatan, r.kelurahan, r.notif_lasik, r.notif_eklp
    FROM result r
    JOIN parents p ON r.parent_id = p.id
    WHERE r.kpj IS NOT NULL AND r.kpj != '' AND r.nama IS NOT NULL AND r.nama != ''
  `;
  let params = [];
  if (parentId !== null) {
    query += ' AND r.parent_id = ?';
    params.push(parentId);
  } else {
    query += ' AND p.is_file = ?';
    params.push(is_file ? 1 : 0);
  }
  const [rows] = await db.query(query, params);

  // 生成 Excel
  const workbook = new ExcelJS.Workbook();
  const sheet = workbook.addWorksheet('Export');
  // 第一行：MODE
  sheet.addRow([`MODE: ${mode.replace(/_/g, ' → ').toUpperCase()}`]);
  // 空一行
  sheet.addRow([]);
  // 表头
  sheet.addRow(columns.map(col => col.header));
  // 数据
  rows.forEach(row => {
    const data = columns.map(col => row[col.key] || 'N/A');
    console.log('导出数据:', data);
    sheet.addRow(data);
  });
  // 设置响应头
  res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  res.setHeader('Content-Disposition', `attachment; filename=export_all_${mode}.xlsx`);
  await workbook.xlsx.write(res);
  res.end();
});

app.listen(3000, () => {
  console.log("API running on http://localhost:3000");
});