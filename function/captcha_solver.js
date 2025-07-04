import fs from "fs";
import path from "path";
import { Client } from "@gradio/client";
import { v4 as uuidv4 } from "uuid";

export async function solveCaptchaByScreenshot(page) {
  const captchaElement = await page.$("#img_captcha");
  if (!captchaElement) {
    console.log("Captcha element tidak ditemukan");
    return null;
  }
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