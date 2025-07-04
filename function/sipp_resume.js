// /function/sipp_resume.js
import puppeteer from "puppeteer";
import { login } from "./sipp_login.js";
import { inputDataAndScrape } from "./sipp_scrape.js";
import { safeGoto } from "./config.js";
import {
  saveChild,
  updateStatusParent
} from "../database/function.js";
import db from "../database/db.js";
import dotenv from "dotenv";
dotenv.config();

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

/* ────────────────────────── resumeParent ───────────────────────── */
export async function resumeParent({ parentId }) {
  const parent = await getParentById(parentId);
  if (!parent) throw new Error(`Parent id ${parentId} tidak ditemukan`);
  console.log(`▶️  Resume parent KPJ ${parent.kpj} (id=${parentId})`);

  /* 1.  Pastikan list child ada */
  let pending = await getPendingChildren(parentId);
  if (pending.length === 0) {
    // belum pernah dibuat → generate lagi berdasarkan pola x‑y‑z
    const kpjStr = parent.kpj.toString();
    const x = Number(kpjStr.slice(5, 7));       // "43"
    const y = Number(kpjStr.slice(7, 9));       // "yy"
    const zParent = Number(kpjStr.slice(9, 11));  // "zz"
    const parent_z = [0,1,2,3,4,5,6,7,8,9];
    const child_z = [
      [0,18,26,34,42,59,67,75,83,91],
      [1,19,27,35,43,50,60,68,76,84,92],
      [2,10,28,36,44,51,69,77,85,93],
      [3,11,29,37,45,52,60,78,86,94],
      [4,12,20,38,46,53,61,79,87,95],
      [5,13,21,39,47,54,62,70,88,96],
      [6,14,22,30,48,55,63,71,89,97],
      [7,15,23,31,49,56,64,72,80,98],
      [8,16,24,32,40,57,65,73,81,99],
      [9,17,25,33,41,58,66,74,82,90],
    ];
    const idx = parent_z.indexOf(zParent);
    for (const z of child_z[idx]) {
      const childKpj = Number(`11017${pad2(x)}${pad2(y)}${pad2(z)}`);
      await saveChild(
        { kpj: childKpj, sipp_status: "pending", percobaan: 0 },
        parentId
      );
    }
    pending = await getPendingChildren(parentId);
  }

  /* 2.  Launch browser (profil unik via PUP_PROFILE) */
  const browser = await puppeteer.launch({
    headless: true,
    userDataDir: process.env.PUP_PROFILE,   // ⬅️ cache terpisah
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"]
  });
  const page = await browser.newPage();
  await login(page);

  /* 3.  Kerjakan setiap child yang masih pending */
  for (const child of pending) {
    console.log(`   ↪️  Proses child KPJ ${child.kpj}`);
    await safeGoto(page, INPUT_PAGE_URL);
    let result = await inputDataAndScrape(page, { kpj: child.kpj });
    result.sipp_status = "success";
    await saveChild(result, parentId);
    await new Promise(r => setTimeout(r, 5000));
  }

  /* 4.  Semua child done → parent sukses */
  await updateStatusParent(parentId, "success");
  console.log(`✅ Parent ${parent.kpj} selesai`);
  await browser.close();
}

/* ────────────────────────── resumeChild ───────────────────────── */
export async function resumeChild({ childId }) {
  const child = await getChildById(childId);
  if (!child) throw new Error(`Child id ${childId} tidak ditemukan`);

  console.log(`▶️  Resume child KPJ ${child.kpj} (id=${childId})`);

  const browser = await puppeteer.launch({
    headless: true,
    userDataDir: process.env.PUP_PROFILE,
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"]
  });
  const page = await browser.newPage();
  await login(page);

  await safeGoto(page, INPUT_PAGE_URL);
  let result = await inputDataAndScrape(page, { kpj: child.kpj });
  result.sipp_status = "success";
  await saveChild(result, child.parent_id);      // overwrite

  await browser.close();

  /* cek apakah parent‐nya sudah tidak ada pending lagi */
  const remaining = await getPendingChildren(child.parent_id);
  if (remaining.length === 0) {
    await updateStatusParent(child.parent_id, "success");
    console.log(`✅ Parent id ${child.parent_id} ikut selesai`);
  }
}
