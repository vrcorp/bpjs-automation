import puppeteer from "puppeteer";
import { login } from "../function/sipp_login.js";
import { inputDataAndScrape } from "../function/sipp_scrape.js";
import { safeGoto } from "../function/config.js";
import { saveChild, updateStatusParent } from "../database/function.js";
import db from "../database/db.js";
import dotenv from "dotenv";
dotenv.config();

let browser = null; // <‑‑ cache di level module
// Konfigurasi
const INPUT_PAGE_URL = process.env.SIPP_INPUT_URL;
const pad2 = (n) => n.toString().padStart(2, "0");

/* ───────────────────────── helpers DB ────────────────────────── */
export async function getParentById(id) {
  const [rows] = await db.query("SELECT * FROM parents WHERE id = ?", [id]);
  return rows[0] || null;
}

export async function getChildById(id) {
  const [rows] = await db.query("SELECT * FROM result WHERE id = ?", [id]);
  return rows[0] || null;
}

export async function getPendingChildren(parentId) {
  const [rows] = await db.query(
    `SELECT * FROM result
       WHERE parent_id = ? AND (sipp_status IS NULL OR sipp_status NOT IN ('success','not found'))`,
    [parentId]
  );
  return rows;
}

export async function resumeChild({ childId }) {
  const action = "start";
  try {
    /* ─────────────  START  ───────────── */
    if (action === "start") {
      if (browser) {
        // sudah jalan? skip
        console.log("Browser masih hidup, pake yang lama 🚀");
        return;
      }
      const profileDir = `${process.env.PUP_PROFILE}/child_${childId}`;
      // contoh hasil: F:/WEB/SCRAPE/cache/child_42
      await fs.promises.mkdir(profileDir, { recursive: true });

      browser = await puppeteer.launch({
        headless: true, // captchanya manual? set false
        defaultViewport: null,
        userDataDir: profileDir,
        dumpio: true,
        args: [
          "--no-sandbox",
          "--disable-setuid-sandbox",
          "--disable-dev-shm-usage",
        ],
      });

      const page = await (browser ?? await puppeteer.launch(opts)).newPage();
      await page.setUserAgent(
        "Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/15.0 Mobile/15E148 Safari/604.1"
      );
      await page.setViewport({ width: 390, height: 844 });
      await page.evaluateOnNewDocument(() =>
        Object.defineProperty(navigator, "webdriver", { get: () => false })
      );

      console.log("Menunggu login manual…");
      await login(page);

      await runDefaultFlow(page, childId); // ↖️ Pindahkan loop‑loop mu ke fungsi ini

      console.log("🎉 Flow selesai, panggil action:'stop' utk nutup browser");
      return;
    }

    /* ─────────────  STOP  ───────────── */
    if (action === "stop") {
      if (!browser) {
        console.log("Belum ada browser yang aktif 🤷‍♂️");
        return;
      }
      await browser.close();
      browser = null;
      console.log("🛑 Browser dimatikan");
      return;
    }

    throw new Error(`Action '${action}' gak dikenal`);
  } catch (err) {
    console.error("❌  Error:", err);
    // pastikan browser ditutup kalau error
    if (browser) {
      await browser.close();
      browser = null;
    }
  }
}

/* ───────────────── helper ───────────────── */
async function runDefaultFlow(page, childId) {
  const child = await getChildById(childId);
  if (!child) throw new Error(`Child id ${childId} tidak ditemukan`);

  console.log(`▶️  Resume child KPJ ${child.kpj} (id=${childId})`);

  await safeGoto(page, INPUT_PAGE_URL);
  await page.screenshot({ path: "debug.png", fullPage: true });
  let result = await inputDataAndScrape(page, { kpj: child.kpj });
  result.sipp_status = "success";
  await saveChild(result, child.parent_id); // overwrite

  await browser.close();
  browser = null; // ← penting!

  /* cek apakah parent‐nya sudah tidak ada pending lagi */
  const remaining = await getPendingChildren(child.parent_id);
  if (remaining.length === 0) {
    await updateStatusParent(child.parent_id, "success");
    console.log(`✅ Parent id ${child.parent_id} ikut selesai`);
  }
}

async function runFileFlow(page, file) {
  // flow kalau pakai file input
}
