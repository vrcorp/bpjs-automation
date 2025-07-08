import puppeteer from "puppeteer";
import { login } from "../function/sipp_login.js";
import { inputDataAndScrape } from "../function/sipp_scrape.js";
import { safeGoto } from "../function/config.js";
import { getSelectedInduk } from "../database/function.js";
import dotenv from "dotenv";
import {
  saveParent,
  saveChild,
  updateStatusParent,
  checkParentStatus,
  checkChildStatus,
  getChildrenByParentId,
  getParentById,
  getAllFileParents
} from "../database/function.js";
import { openTab, closeTab } from "../browser/browserManager.js";
dotenv.config();

// Konfigurasi
const INPUT_PAGE_URL = process.env.SIPP_INPUT_URL;

export async function generateSipp({
  mode = "default",
  is_file = false,
  action = "start",
  config = null,
  parentId  = null
} = {}) {
  const jobId = "generate-sipp";
  
  try {
    /* ─────────────  START  ───────────── */
    if (action === "start") {
      // Konfigurasi khusus untuk generate (desktop-like)
      const page = await openTab(jobId, {
        viewport: { width: 1366, height: 768 },
        userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
      });
      
      // login hanya sekali per tab
      if (page.__logged !== true) {
        await login(page);
        page.__logged = true;
      }
      
      if (is_file === false) {
        if (parentId !== null) {
          await runFlowParent(page, parentId);
        } else {
          await runDefaultFlow(page, parentId);
        }
      } else if (is_file === true) {
        await runFileFlow(page,parentId);
      }
      
      console.log("🎉 Flow selesai");
      // Jangan tutup tab di sini, biarkan user yang tutup dengan action:'stop'
      return;
    }
    
    /* ─────────────  STOP  ───────────── */
    if (action === "stop") {
      await closeTab(jobId);
      return;
    }
    
    throw new Error(`Action '${action}' tidak dikenal`);
    
  } catch (err) {
    console.error("❌ Error in generateSipp:", err);
    // pastikan browser ditutup kalau error
    await closeTab(jobId);
    throw err; // Re-throw untuk handling di level atas
  }
}

/* ───────────────── helper ───────────────── */
async function runDefaultFlow(page, parentId=null) {
  const induxx = await getSelectedInduk();
  // const x = 43;
  const pad2 = (n) => n.toString().padStart(2, "0");
  const parent_z = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
  const child_z = [
    [0, 18, 26, 34, 42, 59, 67, 75, 83, 91],
    [1, 19, 27, 35, 43, 50, 60, 68, 76, 84, 92],
    [2, 10, 28, 36, 44, 51, 69, 77, 85, 93],
    [3, 11, 29, 37, 45, 52, 60, 78, 86, 94],
    [4, 12, 20, 38, 46, 53, 61, 79, 87, 95],
    [5, 13, 21, 39, 47, 54, 62, 70, 88, 96],
    [6, 14, 22, 30, 48, 55, 63, 71, 89, 97],
    [7, 15, 23, 31, 49, 56, 64, 72, 80, 98],
    [8, 16, 24, 32, 40, 57, 65, 73, 81, 99],
    [9, 17, 25, 33, 41, 58, 66, 74, 82, 90],
  ];
  
  for (let y = 1; y <= 99; y++) {
    for (let pIdx = 0; pIdx < parent_z.length; pIdx++) {
      const parent = parent_z[pIdx];
      const zParent = parent;
      const parentKpj = Number(`${induxx}${pad2(y)}${pad2(zParent)}`);
      
      // check status db
      const hasChecked = await checkParentStatus(parentKpj);
      if (hasChecked == "success" || hasChecked == "not found") {
        console.log(`✅ Parent ${parentKpj} sudah diproses`);
        continue;
      }
      
      console.log(`🔍 Cek parent KPJ: ${parentKpj}`);
      await safeGoto(page, INPUT_PAGE_URL);
      
      const parentResult = await inputDataAndScrape(page, {
        kpj: parentKpj,
      });
      
      console.log(parentResult);
      await new Promise((resolve) => setTimeout(resolve, 5000));
      
      if (
        parentResult.keterangan === "Sukses" ||
        parentResult.keterangan === "Tidak bisa digunakan"
      ) {
        console.log(`✅ Parent KPJ ${parentKpj} sukses, lanjut child...`);
        parentResult.status = "processing";
        const parentId = await saveParent(parentResult);
        
        for (const z of child_z[pIdx]) {
          const childKpj = Number(`${induxx}${pad2(y)}${pad2(z)}`);
          console.log(`   ↳ Cek child KPJ: ${childKpj}`);
          
          // check status db
          const hasCheckedChild = await checkChildStatus(childKpj);
          if (hasCheckedChild !== null || hasCheckedChild == "success") {
            console.log(`✅ Child ${childKpj} sudah diproses`);
            continue;
          }
          
          let childResult = {
            nik: "",
            nama_lengkap: "",
            kpj: childKpj,
            tempat_lahir: "",
            tgl_lahir: "",
            email: "",
            no_handphone: "",
            keterangan: null,
            sipp_status: "pending",
            percobaan: 1,
          };
          
          await saveChild(childResult, parentId);
          await safeGoto(page, INPUT_PAGE_URL);
          
          childResult = await inputDataAndScrape(page, {
            kpj: childKpj,
          });
          
          console.log(childResult, parentId);
          childResult.sipp_status = "success";
          await saveChild(childResult, parentId);
          
          await new Promise((resolve) => setTimeout(resolve, 5000));
        }
        
        await updateStatusParent(parentId, "success");
        
      } else {
        parentResult.status = "not found";
        parentResult.kpj = parentKpj;
        const parentId = await saveParent(parentResult);
        console.log(`❌ Parent KPJ ${parentKpj} gagal atau bukan targetParent`);
      }
    }
  }
  
  console.log("All data processed successfully!");
  // Jangan tutup tab di sini, biarkan user yang tutup dengan action:'stop'
}

async function runFlowParent(page, parentId) {
  const induxx = await getSelectedInduk();
  const pad2 = (n) => n.toString().padStart(2, "0");
  const parent_z = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
  const child_z = [
    [0, 18, 26, 34, 42, 59, 67, 75, 83, 91],
    [1, 19, 27, 35, 43, 50, 60, 68, 76, 84, 92],
    [2, 10, 28, 36, 44, 51, 69, 77, 85, 93],
    [3, 11, 29, 37, 45, 52, 60, 78, 86, 94],
    [4, 12, 20, 38, 46, 53, 61, 79, 87, 95],
    [5, 13, 21, 39, 47, 54, 62, 70, 88, 96],
    [6, 14, 22, 30, 48, 55, 63, 71, 89, 97],
    [7, 15, 23, 31, 49, 56, 64, 72, 80, 98],
    [8, 16, 24, 32, 40, 57, 65, 73, 81, 99],
    [9, 17, 25, 33, 41, 58, 66, 74, 82, 90],
  ];

  // 查询 parent 数据
  const parent = await getParentById(parentId); // 你需要实现 getParentById
  if (!parent) throw new Error('Parent not found');
  // 解析 parent_z
  const parentKpjStr = String(parent.kpj);
  const zParent = parseInt(parentKpjStr.slice(-2), 10); // 取后两位
  if (isNaN(zParent) || zParent < 0 || zParent > 9) throw new Error('parent_z 无效');

  for (let y = 1; y <= 99; y++) {
    for (const z of child_z[zParent]) {
      const childKpj = Number(`${induxx}${pad2(y)}${pad2(z)}`);
      // check status db
      const hasCheckedChild = await checkChildStatus(childKpj);
      if (hasCheckedChild !== null || hasCheckedChild == "success") {
        console.log(`✅ Child ${childKpj} sudah diproses`);
        continue;
      }
      let childResult = {
        nik: "",
        nama_lengkap: "",
        kpj: childKpj,
        tempat_lahir: "",
        tgl_lahir: "",
        email: "",
        no_handphone: "",
        keterangan: null,
        sipp_status: "pending",
        percobaan: 1,
      };
      await saveChild(childResult, parentId);
      await safeGoto(page, INPUT_PAGE_URL);
      childResult = await inputDataAndScrape(page, { kpj: childKpj });
      childResult.sipp_status = "success";
      await saveChild(childResult, parentId);
      await new Promise((resolve) => setTimeout(resolve, 5000));
    }
  }
  await updateStatusParent(parentId, "success");
  console.log(`🎉 Semua child untuk parent ${parent.kpj} selesai diproses!`);
}

async function runFileFlow(page, parentId) {
  let parents = [];
  if (parentId) {
    // 只处理指定的 parentId
    const parent = await getParentById(parentId,true);
    if (!parent) {
      console.log(`❌ 未找到 parentId: ${parentId}`);
      return;
    }
    parents = [parent];
  } else {
    // 处理所有 is_file = true 的 parent
    parents = await getAllFileParents(); // 你需要在 database/function.js 实现 
    if (!parents || parents.length === 0) {
      console.log('❌ 未找到任何 is_file = true 的 parent');
      return;
    }
  }

  for (const parent of parents) {
    const curParentId = parent.id;
    // 查询该 parent 下所有 child
    const children = await getChildrenByParentId(curParentId); // 你需要在 database/function.js 实现 getChildrenByParentId()
    for (const child of children) {
      // 跳过已处理的 child
      if (child.sipp_status === 'success' || child.sipp_status === 'not found') {
        console.log(`✅ Child ${child.kpj} 已处理，跳过`);
        continue;
      }
      console.log(`🔍 Scrape child KPJ: ${child.kpj}`);
      await safeGoto(page, INPUT_PAGE_URL);
      let childResult = await inputDataAndScrape(page, { kpj: child.kpj });
      childResult.sipp_status = 'success';
      await saveChild({ ...child, ...childResult }, curParentId);
      await new Promise((resolve) => setTimeout(resolve, 3000));
    }
    await updateStatusParent(curParentId, 'success');
  }
  console.log('🎉 Semua data file selesai diproses!');
}