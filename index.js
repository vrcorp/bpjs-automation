import express from "express";
import {resumeChild} from "./function/resumeChild_sipp.js";
import {generateSipp} from "./function/generateSipp.js";
import {runParentById} from "./function/resumeParent_sipp.js";
import {scrapeDpt} from "./function/dptScrape.js";
import {scrapeLasik} from "./function/lasikScraper.js";
import {scrapeEklp} from "./function/eklpScraper.js";
import bodyParser from "body-parser";

const app = express();
app.use(express.json());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

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

app.listen(3000, () => {
  console.log("API running on http://localhost:3000");
});