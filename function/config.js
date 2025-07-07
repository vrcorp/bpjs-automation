import fs from "fs";
import path from "path";
import '../function/logger.js'
import { session } from "../function/session.js";

export async function safeGoto(page, url, options = {}, maxRetries = 3) {
  for (let attempt = 1; attempt <= maxRetries; attempt++) {

    // 🔒  Kalau tab sudah tutup, hentikan proses
    if (page.isClosed()) {
      throw new Error(`Tab sudah ditutup sebelum mulai navigate ke ${url}`);
    }

    try {
      await page.goto(url, {
        waitUntil: "networkidle2",
        timeout: 30_000,
        ...options,
      });
      return;                       // ✅ sukses, keluar fungsi
    } catch (err) {

      // ❗  Kalau tab tutup di tengah jalan, stop retry
      if (page.isClosed() || /Target closed/.test(err.message)) {
        throw new Error(`Tab ter-close saat navigate: ${err.message}`);
      }

      console.warn(
        `❌ Gagal buka halaman (percobaan ${attempt}/${maxRetries}): ${err.message}`
      );

      if (attempt === maxRetries) {
        throw new Error(`Gagal membuka ${url} setelah ${maxRetries} kali percobaan`);
      }

      console.log("🔄 Retry dalam 3 detik…");
      await new Promise(r => setTimeout(r, 3000));
    }
  }
}

