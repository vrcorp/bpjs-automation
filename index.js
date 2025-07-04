import {
  runGenerate,
  runParentById,
  runChildById
} from "./utils/pm2Runner.js";

const app = express();
app.use(express.json());            // biar gampang ambil body JSON

/* ───── global generate (loop) ───── */
app.post("/generate", async (req, res) => {
  const mode = req.body.mode || "default";
  try {
    const out = await runGenerate(mode);
    res.json({ status: "OK", message: out });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
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
app.post("/resume-child/:id", async (req, res) => {
  try {
    const out = await runChildById(req.params.id);
    res.json({ status: "OK", message: out });
  } catch (e) {
    res.status(500).json({ status: "ERROR", message: e.message });
  }
});

app.listen(3000, () => {
  console.log("API running on http://localhost:3000");
});
