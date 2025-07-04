import puppeteer from "puppeteer";
import XLSX from "xlsx";
import dotenv from "dotenv";
import {
  saveParent,
  saveChild,
  updateStatusParent,
  checkParentStatus,
  checkChildStatus,
} from "./database/function.js";
import { login } from "./function/sipp_login.js";
import { inputDataAndScrape } from "./function/sipp_scrape.js";
import { safeGoto } from "./function/config.js";
dotenv.config();

// Konfigurasi
const INPUT_PAGE_URL = process.env.SIPP_INPUT_URL;

export async function generateSipp({ mode = "default", file = null }) {
  try {
    const browser = await puppeteer.launch({
      headless: true, // Wajib false agar bisa input captcha manual
      defaultViewport: null,
      // args: ["--start-maximized"],
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
      ],
    });

    const page = await browser.newPage();

    await page.setUserAgent(
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    );
    await page.setViewport({ width: 1366, height: 768 });

    await page.evaluateOnNewDocument(() => {
      Object.defineProperty(navigator, "webdriver", { get: () => false });
    });

    // Login dengan captcha manual
    console.log("Menunggu proses login ...");
    await login(page);

    if (mode == "default") {
      // Buat dataToInput dari hasil kombinasi loop
      const x = 43;
      // const targetChildren = [40, 65, 81];

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
          const zParent = parent; // ambil angka z dari parent_z
          const parentKpj = Number(`11017${pad2(x)}${pad2(y)}${pad2(zParent)}`);

          // check status db
          const hasChecked = await checkParentStatus(parentKpj);
          if (hasChecked == "success" || hasChecked == "not found") {
            console.log(`sudah proses ke db`);
            continue; // Lanjut ke next pIdx (loop berikutnya)
          }

          console.log(`🔍 Cek parent KPJ: ${parentKpj}`);

          await safeGoto(page, INPUT_PAGE_URL);
          const parentResult = await inputDataAndScrape(page, {
            kpj: parentKpj,
          });
          console.log(parentResult);

          // await page.waitForTimeout(5000);
          await new Promise((resolve) => setTimeout(resolve, 5000)); // ✅ BENAR

          if (
            parentResult.keterangan === "Sukses" ||
            parentResult.keterangan === "Tidak bisa digunakan"
          ) {
            console.log(`✅ Parent KPJ ${parentKpj} sukses, lanjut child...`);
            parentResult.status = "processing";
            const parentId = await saveParent(parentResult);

            for (const z of child_z[pIdx]) {
              const childKpj = Number(`11017${pad2(x)}${pad2(y)}${pad2(z)}`);
              console.log(`   ↳ Cek child KPJ: ${childKpj}`);
              // check status db
              const hasCheckedChild = await checkChildStatus(childKpj);
              if (hasCheckedChild !== null || hasCheckedChild == "success") {
                console.log(`sudah proses ke db`);
                continue; // Lanjut ke next pIdx (loop berikutnya)
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
              // scrapedResults.push(childResult);
              // ⬇️ Save ke Excel langsung
              // saveToExcel(scrapedResults); // ✅ real-time save
              childResult.sipp_status = "success";
              await saveChild(childResult, parentId);

              // await page.waitForTimeout(5000);
              await new Promise((resolve) => setTimeout(resolve, 5000)); // ✅ BENAR
            }

            await updateStatusParent(parentId, "success");
          } else {
            parentResult.status = "not found";
            parentResult.kpj = parentKpj;
            const parentId = await saveParent(parentResult);
            console.log(
              `❌ Parent KPJ ${parentKpj} gagal atau bukan targetParent`
            );
          }
        }
      }
      console.log("All data processed successfully!");
      await browser.close();
    }else{
        
    }

  } catch (error) {
    console.error("An error occurred:", error);
  }
}
