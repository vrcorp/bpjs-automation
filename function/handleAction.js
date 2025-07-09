import { scrapeLasik } from "../function/lasikScrape.js";
import { scrapeDpt } from "../function/dptScrape.js";
import { scrapeEklp } from "../function/eklpScraper.js";

export async function generateAction(mode, child, parentId) {
    if (mode === "sipp_lasik_dpt") {
      const dptPromises = [];
      const action = "start";
  
      // Jalankan LASIK (tanpa di-wrap di Promise.all karena bukan array)
      await scrapeLasik({
        data: { childId: child.id,parentId: parentId },
        action,
        type: "child",
        mode,
      });
  
      // Push DPT ke array promise
      dptPromises.push(
        scrapeDpt({
          data: { childId: child.id, parentId: parentId },
          action,
          type: "child",
          mode,
        })
      );
  
      // Tunggu semua DPT selesai
      await Promise.all(dptPromises);
  
      console.log("Semua job (Lasik→DPT) tiap child beres!");
      console.log("Semua LASIK & DPT rampung!");
    } else if (mode === "sipp_eklp_dpt") {
      const dptPromises = [];
  
      // Jalankan EKLP
      await scrapeEklp({
        data: { childId: child.id, parentId: parentId },
        action,
        type: "child",
        mode,
      });
  
      // Push DPT ke array promise
      dptPromises.push(
        scrapeDpt({
          data: { childId: child.id, parentId: parent.id },
          action,
          type: "child",
          mode,
        })
      );
  
      // Tunggu semua DPT selesai
      await Promise.all(dptPromises);
  
      console.log("Semua EKLP & DPT rampung!");
    } else if (mode === "sipp_dpt") {
      await scrapeDpt({
        data: { childId: child.id, parentId: parent.id },
        action,
        type: "child",
        mode,
      });
    }
  }
  