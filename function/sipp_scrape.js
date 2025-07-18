import dotenv from "dotenv";
import { safeGoto } from '../function/config.js';
dotenv.config();

// -----------------------------------------------------------------------------
// Utility
// -----------------------------------------------------------------------------
/**
 * Checks whether the “account-content” element – which only appears after a user
 * has successfully logged in – is present on the page.  The check only waits
 * up to 5 seconds so that the caller does not block for too long.
 *
 * @param {import('puppeteer').Page} page Puppeteer page instance.
 * @returns {Promise<boolean>} Resolves to true if the element appears, false otherwise.
 */
export async function hasLogin(page) {
  try {
    // Wait up to 5 seconds for the element.  If it shows up we consider the user
    // as logged-in.
    await page.waitForSelector('.account-content', { timeout: 5000 });
    return true;
  } catch (_err) {
    // Selector not found within timeout → user is not logged-in.
    return false;
  }
}


const URL_SETELAH_POPUP = process.env.SIPP_SETELAH_POPUP;

export async function inputDataAndScrape(page, data, tried = 1) {
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
    }else{
      console.log("tidak ada data kpj");
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
          content.includes("atas nama")
        ) {
          const namaPeserta = content.match(/atas nama (.*?) terdaftar/i)?.[1] || "";

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
        (modalInfo.message.includes("atas nama") || modalInfo.message.includes("sudah tidak dapat digunakan"))
      ) {
        let nama_lengkapp = modalInfo.nama_peserta;
        console.log("🎯 Hasil nama dari modal:", nama_lengkapp);
        let ket = modalInfo.message.includes("sudah tidak dapat digunakan") ? "Tidak bisa digunakan" : "Sukses";
        console.log("Pesan modal cocok, lanjutkan proses.");
        await safeGoto(page, URL_SETELAH_POPUP, {
          waitUntil: "networkidle2",
        });
        await page.waitForSelector("form", { timeout: 10000 });
        // mauskan modal_info ke formData
        const formData = await page.evaluate(() => {
          return {
            nama_lengkap: document.querySelector('input[name="nama_lengkap"]')?.value || "",
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
            email: document.querySelector('input[name="email"]')?.value || "",
            no_handphone:
              document.querySelector('input[name="no_handphone"]')?.value || "",
          };
        });

        return {
          kpj: data.kpj,
          sipp_status: "success",
          percobaan: tried,
          ...formData,
          keterangan: ket,
          nama_lengkap: nama_lengkapp && nama_lengkapp.trim() !== "" ? nama_lengkapp : formData.nama_lengkap,
        };
      } else {
        let keterangan = "Not Found";

        
        const formData = {
          nik: "",
          tempat_lahir: "",
          tgl_lahir: "",
          jenis_kelamin: "",
          ibu_kandung: "",
          email: "",
          no_handphone: "",
          keterangan: keterangan,
        };

        return {
          kpj: data.kpj,
          nama_lengkap: "",
          percobaan: tried,
          sipp_status: "success",
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
        email: "",
        no_handphone: "",
        keterangan: "Not Found Modal",
      };

      return {
        kpj: data.kpj || "",
        nama_lengkap: "",
        sipp_status: "success",
        percobaan: tried,
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
      email: "",
      no_handphone: "",
      keterangan: "ERROR",
    };

    return {
      kpj: data.kpj || "",
      nama_lengkap: "",
      sipp_status: "success",
      percobaan: tried,
      ...formData,
    };
  }
}
