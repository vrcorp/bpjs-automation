import dotenv from "dotenv";
import { solveCaptchaByScreenshot } from '../function/captcha_solver.js';
import '../function/logger.js';
import { safeGoto } from '../function/config.js';

dotenv.config();

const LOGIN_URL = process.env.SIPP_LOGIN_URL;
const USERNAME = process.env.SIPP_USERNAME;
const PASSWORD = process.env.SIPP_PASSWORD;

export async function login(page, attempt = 1) {
  const MAX_ATTEMPT = 5;

  try {
    console.log(`🔐 Login attempt #${attempt} ke ${LOGIN_URL}`);
    await safeGoto(page, LOGIN_URL);
    // await page.screenshot({ path: 'login_debug.png', fullPage: true });
    const captchaText = await solveCaptchaByScreenshot(page);
    if (!captchaText) {
      console.log("✅ Sudah login, skip captcha");
      return true;
    }

    // 检查用户名输入框是否存在，如果不存在则说明已经登录，直接返回
    const usernameInput = await page.$('input[name="username"]');
    if (!usernameInput) {
      console.log("✅ Sudah login (input username tidak ditemukan), skip login");
      return true;
    }
    await page.type('input[name="username"]', USERNAME);
    await page.type('input[name="password"]', PASSWORD);
    await page.type('input[name="captcha"]', captchaText);
    
    await page.screenshot({ path: 'debug.png', fullPage: true });
    if (!USERNAME) {
      console.log("⚠️ Username tidak ditemukan, skip login");
      return false;
    }

    

    await Promise.all([
      page.click('button[type="submit"]'),
      page.waitForNavigation({ timeout: 5000 }).catch(() => {}) // lanjut walau timeout
    ]);

    // Cek apakah muncul modal error (misalnya captcha salah)
    const modalSelector = '.swal2-modal.swal2-show';
    const modalExists = await page.$(modalSelector);

    if (modalExists) {
      const modalText = await page.evaluate(sel => {
        const el = document.querySelector(sel);
        return el ? el.textContent : "";
      }, modalSelector);

      if (modalText.includes("Captcha tidak sesuai")) {
        console.warn("⚠️ Modal muncul: Captcha salah");

        // Klik OK modal biar bisa lanjut
        // const okBtn = await page.$('.swal2-confirm');
        // if (okBtn) await okBtn.click();

        if (attempt < MAX_ATTEMPT) {
          console.log("🔄 Ulangi login...");
          await new Promise((resolve) => setTimeout(resolve, 5000)); // ✅ BENAR
          return await login(page, attempt + 1);
        } else {
          throw new Error("🚫 Batas login maksimal tercapai.");
        }
      }
    }

    const currentUrl = page.url();
    if (!currentUrl.includes("login")) {
      console.log("✅ Login berhasil!");
      return true;
    } else {
      throw new Error("🚫 Gagal login, kemungkinan captcha salah.");
    }
  } catch (err) {
    console.error("❌ Gagal login:", err.message);
    return false;
  }
}
