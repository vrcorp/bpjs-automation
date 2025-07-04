import dotenv from "dotenv";
import { safeGoto } from "../function/config.js";
import { updateDPT, checkDPTStatus } from './database/function.js';
dotenv.config();

const URL_DPT = process.env.DPT_URL;

export async function scrapeDpt(page, data, tried = 1) {
    const checkStatus = await checkDPTStatus(data.nik, data.parentId);

    if(checkStatus=="success"){
        console.log("Sudah lengkap dpt");
        return;
    }

    let result = {
        kota : "",
        kecamatan :"",
        kelurahan:"",
        percobaan_dpt:1,
        dpt_status:'processing',
        nik:data.nik,
    }
    await updateDPT(result, pdata.parentId);
    
  try {
    await safeGoto(page, URL_DPT, {
      waitUntil: "networkidle2",
    });
    await page.waitForSelector("form", { timeout: 10000 });
    const firstTextInput = await page.$('form input[type="text"]');
    if (firstTextInput) {
      await firstTextInput.click({ clickCount: 3 });
      await firstTextInput.type(data.nik || "", { delay: 50 });

      const searchButton = await page.$x(
        "//button[span[contains(text(),'Pencarian')]]"
      );
      if (searchButton.length > 0) {
        await searchButton[0].click();
      }

      await page.waitForFunction(
        () => {
          const kab = document.querySelector(".row.row-3 p.row--left span");
          const kec = document.querySelector(".row.row-3 p.row--center span");
          const kel = document.querySelector(".row.row-3 p.row--right span");
          return kab && kec && kel;
        },
        { timeout: 10000 }
      );

      const wilayah = await page.evaluate(() => {
        const kab = document.querySelector(".row.row-3 p.row--left");
        const kec = document.querySelector(".row.row-3 p.row--center");
        const kel = document.querySelector(".row.row-3 p.row--right");
        return {
          kabupaten: kab ? kab.childNodes[1]?.textContent?.trim() : null,
          kecamatan: kec ? kec.childNodes[1]?.textContent?.trim() : null,
          kelurahan: kel ? kel.childNodes[1]?.textContent?.trim() : null,
        };
      });

      // 将数据存入 data
      result.kota = wilayah.kabupaten;
      result.kecamatan = wilayah.kecamatan;
      result.kelurahan = wilayah.kelurahan;
      result.percobaan_dpt=1;
      result.dpt_status='success';
      result.nik=data.nik;
      await updateDPT(result, pdata.parentId);
    }
  } catch (error) {
    result = {
        kota : "",
        kecamatan :"",
        kelurahan:"",
        percobaan_dpt:1,
        dpt_status:'error',
        nik:data.nik,
    }
    await updateDPT(result, pdata.parentId);
    console.error("Terjadi kesalahan saat mengambil data DPT:", error);
  }
}
