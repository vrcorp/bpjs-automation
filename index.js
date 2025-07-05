import express from "express";
import {resumeChild} from "./function/resumeChild_sipp.js";
import {generateSipp} from "./function/generateSipp.js";
import bodyParser from "body-parser";

const app = express();
app.use(express.json());            // biar gampang ambil body JSON
app.use(bodyParser.json());            // untuk JSON
app.use(bodyParser.urlencoded({ extended: true })); // untuk form‐urlencoded

/* ───── global generate (loop) ───── */
app.post("/generate", async (req, res) => {
  const mode   = req.body.mode   ?? "default";
  const file   = req.body.file   ?? null;
  const action = req.body.action ?? "start";
  try {
    generateSipp({ mode, file, action })
    .then(() => console.log("✅ generateSipp selesai"))
    .catch(err => console.error("❌ generateSipp error:", err));

  // Balas seketika
  res.status(202).json({ status: "OK", message: "Job diterima & lagi diproses" });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.post('/generate/stop', async (_, res) => {
  await generateSipp({ action: 'stop' });
  res.json({ status: 'OK', msg: 'tab generate ditutup' });
});



/* ───── resume parent by id ───── */
app.post("/resume-parent/:id", async (req, res) => {
  try {
    const out = await runParentById(req.params.id);
    res.json({ status: "OK", message: out });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

/* ───── resume child by id ───── */
app.post('/resume-child/:id', async (req, res) => {
  resumeChild({ childId: req.params.id });
  res.status(202).json({ status: 'OK', msg: 'child resume jalan' });
});

app.post('/resume-child/:id/stop', async (req, res) => {
  await resumeChild({ childId: req.params.id, action: 'stop' });
  res.json({ status: 'OK', msg: 'tab child ditutup' });
});

app.listen(3000, () => {
  console.log("API running on http://localhost:3000");
});
