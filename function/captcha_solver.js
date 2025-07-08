import fs from "fs/promises";
import path from "path";
import { Client } from "@gradio/client";
import { v4 as uuidv4 } from "uuid";

export async function solveCaptchaByScreenshot(page, jenis = "sipp") {
  // 1️⃣  Pastikan gambar sudah betul-betul load & ukurannya final
  await page.waitForFunction((jenis) => {
    let img;
    if (jenis === "sipp") {
      img = document.querySelector('#img_captcha');
    } else {
      img = Array.from(document.querySelectorAll('img')).find(i => i.alt === 'Captcha');
    }
    return img && img.complete && img.naturalWidth > 30;   // sesuaikan kalau perlu
  }, { timeout: 5_000 });

  // 2️⃣  Copy piksel ke <canvas> & ambil DataURL (PNG/JPEG)
  const dataUrl = await page.evaluate((jenis) => {
    if (jenis === "sipp") {
      img = document.querySelector('#img_captcha');
    } else {
      img = Array.from(document.querySelectorAll('img')).find(i => i.alt === 'Captcha');
    }
    const c   = document.createElement('canvas');
    c.width  = img.naturalWidth;
    c.height = img.naturalHeight;
    const ctx = c.getContext('2d');
  
    // --- tambahkan dua baris berikut ---
    ctx.fillStyle = '#FFFFFF';            // isi background putih
    ctx.fillRect(0, 0, c.width, c.height);
  
    ctx.drawImage(img, 0, 0);
  
    // JPEG sudah aman, tak jadi hitam
    return c.toDataURL('image/jpeg', 0.95);   // kualitas 95 %
  });

  // 3️⃣  DataURL → Buffer
  const buffer = Buffer.from(dataUrl.split(',')[1], 'base64');

  // 4️⃣  Simpan ke folder (debug atau audit)
  await fs.mkdir('captcha', { recursive: true });
  const fileName = `captcha_${uuidv4()}.jpg`;              // ekstensi sesuai format di step 2
  const savePath = path.join('captcha', fileName);
  await fs.writeFile(savePath, buffer);
  console.log(`✅ Captcha tersimpan: ${savePath}`);

  // 5️⃣  Kirim ke model OCR
  const client  = await Client.connect('Nischay103/captcha_recognition');
  const result  = await client.predict('/predict', { input: buffer });
  const text    = result.data?.[0]?.trim() ?? '';

  console.log('Hasil OCR Captcha:', text);
  return text;
}
