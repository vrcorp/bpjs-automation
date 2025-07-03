import puppeteer from "puppeteer";
import XLSX from "xlsx";
import fs from "fs";
import path from "path";
import { Client } from "@gradio/client";
import { v4 as uuidv4 } from "uuid";

// Buat file log (akan menambahkan jika sudah ada)
const logStream = fs.createWriteStream(path.join("./log.txt"), { flags: "a" });

// Simpan console.log asli
const originalLog = console.log;

// Override console.log
console.log = (...args) => {
  const timestamp = new Date().toISOString();
  const message = args.join(" ");

  // Tulis ke file dengan timestamp
  logStream.write(`[${timestamp}] ${message}\n`);

  // Tetap tampil di terminal
  originalLog(...args);
};

async function safeGoto(page, url, options = {}, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      await page.goto(url, { waitUntil: "networkidle2", timeout: 30000, ...options });
      return; // Berhasil
    } catch (error) {
      console.warn(`❌ Gagal membuka halaman (percobaan ${attempt}): ${error.message}`);

      if (attempt < maxRetries) {
        console.log(`🔄 Reload halaman dalam 3 detik...`);
        await new Promise((resolve) => setTimeout(resolve, 3000));
      } else {
        throw new Error(`Gagal membuka halaman setelah ${maxRetries} kali: ${url}`);
      }
    }
  }
}


// Konfigurasi
const LOGIN_URL = "https://sipp.bpjsketenagakerjaan.go.id/sipp"; // Sesuaikan dengan URL login yang benar
const INPUT_PAGE_URL =
  "https://sipp.bpjsketenagakerjaan.go.id/tenaga-kerja/baru/form-tambah-tk-individu";
const URL_SETELAH_POPUP =
  "https://sipp.bpjsketenagakerjaan.go.id/tenaga-kerja/baru/form-tambah/kpj";
// const EXCEL_INPUT_PATH = path.join(__dirname, "data.xlsx");
// const EXCEL_OUTPUT_PATH = path.join(__dirname, "hasil_scraping.xlsx");

// Kredensial login - ganti dengan username dan password yang sebenarnya
const USERNAME = "Sendiprayoga198@gmail.com";
const PASSWORD = "Bebek1997";

async function main() {
  try {
    // Baca data dari Excel
    // const workbook = XLSX.readFile(EXCEL_INPUT_PATH);
    // const worksheet = workbook.Sheets[workbook.SheetNames[0]];
    // const dataToInput = XLSX.utils.sheet_to_json(worksheet);

    // console.log(
    //   `Loaded ${dataToInput.length} records from Excel for processing`
    // );

    const browser = await puppeteer.launch({
      headless: true, // Wajib false agar bisa input captcha manual
      defaultViewport: null,
      // args: ["--start-maximized"],
      args: [
        "--no-sandbox",
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage"
      ]
    });

    const page = await browser.newPage();

    await page.setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36");
    await page.setViewport({ width: 1366, height: 768 });

    await page.evaluateOnNewDocument(() => {
      Object.defineProperty(navigator, 'webdriver', { get: () => false });
    });

    // Login dengan captcha manual
    console.log("Menunggu proses login ...");
    await login(page);

    // Array untuk menyimpan hasil scraping
    const scrapedResults = [];

    // Proses setiap data
    // for (const [index, data] of dataToInput.entries()) {
    //   console.log(`Processing record ${index + 1} of ${dataToInput.length}`);
    //   console.log(`KPJ: ${data.kpj}`);

    //   // Navigasi ke halaman input data
    //   await page.goto(INPUT_PAGE_URL, { waitUntil: "networkidle2" });

    //   // Input data sesuai form
    //   const result = await inputDataAndScrape(page, data);
    //   scrapedResults.push(result);

    //   // Tambahkan delay untuk menghindari overload server
    //   //   await page.waitForTimeout(1000);
    //   await page.evaluate(
    //     () => new Promise((resolve) => setTimeout(resolve, 2000))
    //   );
    // }

    // Buat dataToInput dari hasil kombinasi loop
    const x = 43;
    let targetParent = 8;
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

    const dataToInput = [];

    for (let y = 1; y <= 99; y++) {
      for (let pIdx = 0; pIdx < parent_z.length; pIdx++) {
        const parent = parent_z[pIdx];
        const zParent = parent; // ambil angka z dari parent_z
        const parentKpj = Number(`11017${pad2(x)}${pad2(y)}${pad2(zParent)}`);

        console.log(`🔍 Cek parent KPJ: ${parentKpj}`);

        await safeGoto(page,INPUT_PAGE_URL);
        const parentResult = await inputDataAndScrape(page, { kpj: parentKpj });

        // scrapedResults.push(parentResult);
        // ⬇️ Save ke Excel langsung
        // saveToExcel(scrapedResults); // ✅ real-time save

        // await page.waitForTimeout(5000);
        await new Promise((resolve) => setTimeout(resolve, 5000)); // ✅ BENAR

        if (
          parentResult.keterangan === "Sukses" ||
          parentResult.keterangan === "Tidak bisa digunakan"
        ) {
          targetParent = parent;
          console.log(`✅ Parent KPJ ${parentKpj} sukses, lanjut child...`);
          for (const z of child_z[pIdx]) {
            const childKpj = Number(`11017${pad2(x)}${pad2(y)}${pad2(z)}`);
            console.log(`   ↳ Cek child KPJ: ${childKpj}`);

            await safeGoto(page,INPUT_PAGE_URL);
            const childResult = await inputDataAndScrape(page, {
              kpj: childKpj,
            });

            scrapedResults.push(childResult);
            // ⬇️ Save ke Excel langsung
            saveToExcel(scrapedResults); // ✅ real-time save

            // await page.waitForTimeout(5000);
            await new Promise((resolve) => setTimeout(resolve, 5000)); // ✅ BENAR
          }
        } else {
          console.log(
            `❌ Parent KPJ ${parentKpj} gagal atau bukan targetParent`
          );
        }
      }
    }

    // Simpan hasil scraping ke Excel
    saveToExcel(scrapedResults);

    console.log("All data processed successfully!");
    await browser.close();
  } catch (error) {
    console.error("An error occurred:", error);
  }
}

async function solveCaptchaByScreenshot(page) {
  const captchaElement = await page.$("#img_captcha");
  const filename = `captcha_${uuidv4()}.jpeg`;
  const savePath = path.join("captcha", filename);

  await captchaElement.screenshot({ path: savePath });
  const buffer = fs.readFileSync(savePath);

  // Kirim ke model OCR Gradio
  const client = await Client.connect("Nischay103/captcha_recognition");
  const result = await client.predict("/predict", { input: buffer });

  const captchaText = result.data?.[0] || "";
  console.log("Hasil OCR Captcha:", captchaText);
  return captchaText;
}
async function login(page) {
  try {
    await safeGoto(page,"https://sipp.bpjsketenagakerjaan.go.id/");

    // Screenshot & solve captcha
    const captchaText = await solveCaptchaByScreenshot(page);

    await page.type('input[name="username"]', USERNAME);
    await page.type('input[name="password"]', PASSWORD);
    await page.type('input[name="captcha"]', captchaText);

    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ timeout: 15000 }).catch(() => {}),
    ]);

    const currentUrl = page.url();
    if (!currentUrl.includes("login")) {
      console.log("✅ Login berhasil!");
      return true;
    } else {
      throw new Error("❌ Login gagal, captcha mungkin salah.");
    }
  } catch (err) {
    console.error("Gagal login:", err.message);
    return false;
  }
}

async function inputDataAndScrape(page, data) {
  try {
    await page.waitForSelector(
      ".btn.btn-primary.btn-bordered.waves-effect.w-md",
      { timeout: 5000 }
    );
    await page.click(".btn.btn-primary.btn-bordered.waves-effect.w-md");
    //   await page.click('a[href="#collapseTwo"]');
    await page.waitForSelector("form", { timeout: 10000 });

    if (data.kpj) {
      await page.type('input[id="kpj"]', data.kpj.toString());
    }

    // Klik tombol cek status
    await page.click('a[onclick="cekStatusAktifTk();"]');

    // Tunggu salah satu modal muncul
    try {
      await page.waitForSelector(".swal2-modal.swal2-show", { timeout: 5000 });

      // Cek tipe modal dan ambil informasi
      const modalInfo = await page.evaluate(() => {
        const modal = document.querySelector(
          '.swal2-modal[style*="display: block"]'
        );
        if (!modal) {
          console.log("Modal tidak ditemukan");
          return {
            status: "Modal tidak muncul",
            message: "",
            nama_peserta: "",
          };
        }

        const successIcon = modal.querySelector(".swal2-icon.swal2-success");
        const errorIcon = modal.querySelector(".swal2-icon.swal2-error");
        const content =
          modal.querySelector(".swal2-content")?.textContent || "";

        // console.log("Isi modal:", content);

        if (
          content &&
          content.includes("silakan lengkapi data profil tenaga kerja.")
        ) {
          const namaPeserta = content.match(/nama (.*?) terdaftar/i)?.[1] || "";
          return {
            status: "Bisa digunakan",
            message: content,
            nama_peserta: namaPeserta,
          };
        } else if (content && content.includes("sudah tidak dapat digunakan")) {
          return {
            status: "Sudah tidak dapat digunakan",
            message: content,
            nama_peserta: "",
          };
        } else {
          return {
            status: "Tidak bisa digunakan",
            message: content,
            nama_peserta: "",
          };
        }
      });

      if (
        modalInfo.message &&
        modalInfo.message.includes("silakan lengkapi data profil tenaga kerja.")
      ) {
        console.log("Pesan modal cocok, lanjutkan proses.");
        await safeGoto(page,URL_SETELAH_POPUP, {
          waitUntil: "networkidle2",
        });
        await page.waitForSelector("form", { timeout: 10000 });
        // mauskan modal_info ke formData
        const formData = await page.evaluate(() => {
          return {
            nik:
              document.querySelector('input[name="no_identitas"]')?.value || "",
            tempat_lahir:
              document.querySelector('input[name="tempat_lahir"]')?.value || "",
            tgl_lahir:
              document.querySelector('input[name="tgl_lahir"]')?.value || "",
            jenis_kelamin:
              document.querySelector('select[name="jenis_kelamin"]')?.value ||
              "",
            ibu_kandung:
              document.querySelector('input[name="ibu_kandung"]')?.value || "",
            keterangan: "Sukses",
          };
        });

        return {
          kpj: data.kpj,
          nama_lengkap: modalInfo.nama_peserta || "",
          ...formData,
        };
      } else {
        let keterangan = "";
        if (modalInfo.message.includes("sudah tidak dapat digunakan")) {
          keterangan = "Tidak bisa digunakan";
        } else {
          keterangan = "Not Found";
        }

        await page.click(".swal2-confirm.swal2-styled");
        const formData = {
          nik: "",
          nama_lengkap: "",
          tempat_lahir: "",
          tgl_lahir: "",
          jenis_kelamin: "",
          ibu_kandung: "",
          keterangan: keterangan,
        };

        return {
          kpj: data.kpj,
          nama_lengkap: "",
          ...formData,
        };
      }
    } catch (modalError) {
      console.error(`Error handling modal for KPJ ${data.kpj}:`, modalError);
      const formData = {
        nik: "",
        tempat_lahir: "",
        tgl_lahir: "",
        jenis_kelamin: "",
        ibu_kandung: "",
        keterangan: "Not Found Modal",
      };

      return {
        kpj: data.kpj || "",
        nama_lengkap: "",
        ...formData,
      };
    }
  } catch (error) {
    console.error(`Error processing data for KPJ ${data.kpj}:`, error);
    const formData = {
      nik: "",
      tempat_lahir: "",
      tgl_lahir: "",
      jenis_kelamin: "",
      ibu_kandung: "",
      keterangan: "ERROR",
    };

    return {
      kpj: data.kpj || "",
      nama_lengkap: "",
      ...formData,
    };
  }
}

function saveToExcel(data) {
  // Buat workbook baru
  const newWorkbook = XLSX.utils.book_new();
  const newWorksheet = XLSX.utils.json_to_sheet(data);

  // Tambahkan worksheet ke workbook
  XLSX.utils.book_append_sheet(newWorkbook, newWorksheet, "Data Scraping");

  // Simpan file dengan timestamp
  const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
  const fileName = `hasil_scraping_${timestamp}.xlsx`;

  XLSX.writeFile(newWorkbook, fileName);
  console.log(`Results saved to ${fileName}`);
}

// Jalankan program
main();
