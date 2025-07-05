import puppeteer from "puppeteer";
import { login } from "../function/sipp_login.js";
import { inputDataAndScrape } from "../function/sipp_scrape.js";
import { safeGoto } from "../function/config.js";
import { saveChild, updateStatusParent } from "../database/function.js";
import db from "../database/db.js";
import dotenv from "dotenv";
dotenv.config();
import { openTab, closeTab } from "../browser/browserManager.js";

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

export async function resumeChild({ childId, action = 'start', config = null }) {
  const jobId = `child-${childId}`;
  
  try {
    if (action === "stop") {
      await closeTab(jobId);
    }
    
    // Validasi childId
    if (!childId) {
      throw new Error("childId is required");
    }
    
    console.log(`🔄 Starting resume for child ID: ${childId}`);
    
    const page = await openTab(jobId, {
      viewport: { width: 414, height: 896 },
      userAgent: "Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36"
    });

    
    // Login hanya jika belum login
    if (page.__logged !== true) {
      await login(page);
      page.__logged = true;
    }
    
    await runDefaultFlow(page, childId);
    
    await closeTab(jobId);
    console.log(`✅ Resume child ${childId} completed successfully`);
    
  } catch (err) {
    console.error(`❌ Error resuming child ${childId}:`, err);
    // pastikan browser ditutup kalau error
    await closeTab(jobId);
    throw err; // Re-throw untuk handling di level atas
  }
}

/* ───────────────── helper ───────────────── */
async function runDefaultFlow(page, childId) {
  const child = await getChildById(childId);
  if (!child) throw new Error(`Child id ${childId} tidak ditemukan`);
  
  console.log(`▶️  Resume child KPJ ${child.kpj} (id=${childId})`);
  
  await safeGoto(page, INPUT_PAGE_URL);
  
  // Optional: Screenshot untuk debug
  // await page.screenshot({ path: `debug-child-${childId}.png`, fullPage: true });
  
  let result = await inputDataAndScrape(page, { kpj: child.kpj });
  result.sipp_status = "success";
  
  await saveChild(result, child.parent_id); // overwrite
  
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