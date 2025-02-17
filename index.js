const puppeteer = require("puppeteer");
const XLSX = require("xlsx");
const fs = require("fs");
const path = require("path");

// Konfigurasi
const LOGIN_URL = "https://sipp.bpjsketenagakerjaan.go.id/sipp"; // Sesuaikan dengan URL login yang benar
const INPUT_PAGE_URL =
  "https://sipp.bpjsketenagakerjaan.go.id/tenaga-kerja/baru/form-tambah-tk-individu";
const URL_SETELAH_POPUP =
  "https://sipp.bpjsketenagakerjaan.go.id/tenaga-kerja/baru/form-tambah/kpj";
const EXCEL_INPUT_PATH = path.join(__dirname, "data.xlsx");
const EXCEL_OUTPUT_PATH = path.join(__dirname, "hasil_scraping.xlsx");

// Kredensial login - ganti dengan username dan password yang sebenarnya
const USERNAME = "mamahmamih8888@gmail.com";
const PASSWORD = "48NJW1U6NJ4";

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

async function login(page) {
  try {
    await page.goto("https://sipp.bpjsketenagakerjaan.go.id/", {
      waitUntil: "networkidle2",
    });



    // Isi username dan password
    await page.type('input[name="username"]', USERNAME);
    await page.type('input[name="password"]', PASSWORD);

    // Tunggu user mengisi captcha secara manual
    console.log("Silakan isi captcha secara manual...");

    // Menunggu sampai URL berubah (indikasi login berhasil)
    await Promise.race([
      page.waitForNavigation({ timeout: 180000 }), // Timeout 2 menit
      new Promise((resolve) => {
        console.log(
          "Anda memiliki 3 menit untuk menyelesaikan captcha dan login"
        );
        setTimeout(resolve, 180000);
      }),
    ]);

    // Verifikasi login berhasil
    const currentUrl = page.url();
    if (currentUrl.includes("https://sipp.bpjsketenagakerjaan.go.id/")) {
      console.log("Login berhasil!");
      return true;
    } else {
      throw new Error("Login gagal atau timeout.");
    }
  } catch (error) {
    console.error("Error during login:", error);
    throw error;
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
