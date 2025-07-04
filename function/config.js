import fs from "fs";
import path from "path";
import '../function/logger.js'
import { session } from "../function/session.js";

export async function safeGoto(page, url, options = {}, maxRetries = 3) {
  session.attempt = 1;
  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      await page.goto(url, { waitUntil: "networkidle2", timeout: 30000, ...options });
      return; // Berhasil
    } catch (error) {
      console.warn(`❌ Gagal membuka halaman (percobaan ${attempt}): ${error.message}`);
      session.attempt += 1;
      if (attempt < maxRetries) {
        console.log(`🔄 Reload halaman dalam 3 detik...`);
        await new Promise((resolve) => setTimeout(resolve, 3000));
      } else {
        throw new Error(`Gagal membuka halaman setelah ${maxRetries} kali: ${url}`);
      }
    }
  }
}
