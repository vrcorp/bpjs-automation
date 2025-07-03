const puppeteer = require("puppeteer");
const XLSX = require("xlsx");
const fs = require("fs");
const path = require("path");
import { Client } from "@gradio/client";
import { v4 as uuidv4 } from "uuid";

// Konfigurasi
const LOGIN_URL = "https://sipp.bpjsketenagakerjaan.go.id/sipp"; // Sesuaikan dengan URL login yang benar
const INPUT_PAGE_URL =
  "https://sipp.bpjsketenagakerjaan.go.id/tenaga-kerja/baru/form-tambah-tk-individu";
const URL_SETELAH_POPUP =
  "https://sipp.bpjsketenagakerjaan.go.id/tenaga-kerja/baru/form-tambah/kpj";
const EXCEL_INPUT_PATH = path.join(__dirname, "data.xlsx");
const EXCEL_OUTPUT_PATH = path.join(__dirname, "hasil_scraping.xlsx");

// Kredensial login - ganti dengan username dan password yang sebenarnya
const USERNAME = "Sendiprayoga198@gmail.com";
const PASSWORD = "Bebek1997";

async function main() {
  try {
    // Baca data dari Excel
    const workbook = XLSX.readFile(EXCEL_INPUT_PATH);
    const worksheet = workbook.Sheets[workbook.SheetNames[0]];
    const dataToInput = XLSX.utils.sheet_to_json(worksheet);

    console.log(
      `Loaded ${dataToInput.length} records from Excel for processing`
    );

    const browser = await puppeteer.launch({
      headless: false, // Wajib false agar bisa input captcha manual
      defaultViewport: null,
      args: ["--start-maximized"],
    });

    const page = await browser.newPage();

    // Login dengan captcha manual
    console.log("Menunggu proses login manual...");
    await login(page);

    // Array untuk menyimpan hasil scraping
    const scrapedResults = [];

    // Proses setiap data
    for (const [index, data] of dataToInput.entries()) {
      console.log(`Processing record ${index + 1} of ${dataToInput.length}`);
      console.log(`KPJ: ${data.kpj}`);

      // Navigasi ke halaman input data
      await page.goto(INPUT_PAGE_URL, { waitUntil: "networkidle2" });

      // Input data sesuai form
      const result = await inputDataAndScrape(page, data);
      scrapedResults.push(result);

      // Tambahkan delay untuk menghindari overload server
      //   await page.waitForTimeout(1000);
      await page.evaluate(
        () => new Promise((resolve) => setTimeout(resolve, 2000))
      );
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
  const captchaElement = await page.$('#img_captcha');
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
    await page.goto("https://sipp.bpjsketenagakerjaan.go.id/", {
      waitUntil: "networkidle2",
    });

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
      ".btn.btn-primary.btn-bordered.waves-effect.w-md"
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
        const modal = document.querySelector('.swal2-modal[style*="display: block"]');
        if (!modal) {
            console.log("Modal tidak ditemukan");
            return { status: "Modal tidak muncul", message: "", nama_peserta: "" };
        }
    
        const successIcon = modal.querySelector(".swal2-icon.swal2-success");
        const errorIcon = modal.querySelector(".swal2-icon.swal2-error");
        const content = modal.querySelector(".swal2-content")?.textContent || "";
    
        // console.log("Isi modal:", content);
    
        if (content && content.includes("silakan lengkapi data profil tenaga kerja.")) {
            const namaPeserta = content.match(/nama (.*?) terdaftar/i)?.[1] || "";
            return { status: "Bisa digunakan", message: content, nama_peserta: namaPeserta };
        } else {
            return { status: "Tidak bisa digunakan", message: content, nama_peserta: "" };
        }
        
    });

      if (modalInfo.message && modalInfo.message.includes("silakan lengkapi data profil tenaga kerja.")) { 
        console.log("Pesan modal cocok, lanjutkan proses.");
        await page.goto(URL_SETELAH_POPUP, {
            waitUntil: "networkidle2",
          });
        await page.waitForSelector("form", { timeout: 10000 });
        // mauskan modal_info ke formData
          const formData = await page.evaluate(() => {
            return {
              nik: document.querySelector('input[name="no_identitas"]')?.value || "",
              tempat_lahir: document.querySelector('input[name="tempat_lahir"]')?.value || "",
              tgl_lahir: document.querySelector('input[name="tgl_lahir"]')?.value || "",
              jenis_kelamin: document.querySelector('select[name="jenis_kelamin"]')?.value || "",
              ibu_kandung:document.querySelector('input[name="ibu_kandung"]')?.value ||"",
              keterangan:"Sukses"
            };
          });

          return {
            kpj:data.kpj,
            nama_lengkap: modalInfo.nama_peserta || "",
            ...formData,
          };
      } else {
        // Jika tidak bisa digunakan, klik OK dan return info modal
        await page.click(".swal2-confirm.swal2-styled");
        const formData = {
            nik:  "",
            nama_lengkap: "",
            tempat_lahir:"",
            tgl_lahir: "",
            jenis_kelamin: "",
            ibu_kandung:"",
            keterangan:"Not Found"
          };
          
          return {
            kpj:data.kpj,
            nama_lengkap: "",
            ...formData,
          };
      }
    } catch (modalError) {
      console.error(`Error handling modal for KPJ ${data.kpj}:`, modalError);
      const formData = {
        nik:  "",
        tempat_lahir:"",
        tgl_lahir: "",
        jenis_kelamin: "",
        ibu_kandung:"",
        keterangan:"Not Found Modal"
      };
      
      return {
        kpj:data.kpj||"",
        nama_lengkap: "",
        ...formData,
      };
    }
  } catch (error) {
    console.error(`Error processing data for KPJ ${data.kpj}:`, error);
    const formData = {
        nik:  "",
        tempat_lahir:"",
        tgl_lahir: "",
        jenis_kelamin: "",
        ibu_kandung:"",
        keterangan:"ERROR"
      };
      
      return {
        kpj:data.kpj||"",
        nama_lengkap:  "",
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
