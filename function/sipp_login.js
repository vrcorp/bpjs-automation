import dotenv from "dotenv";
import { solveCaptchaByScreenshot } from '../function/captcha_solver.js';
import '../function/logger.js';
import { safeGoto } from '../function/config.js';
import {getSelectedAkunSippByTipe} from '../database/function.js';

dotenv.config();

const LOGIN_URL = process.env.SIPP_LOGIN_URL;
// 通过 getSelectedAkunSippByTipe 获取用户名和密码
let USERNAME = null;
let PASSWORD = null;

export async function setSippCredentials() {
  // 默认 tipe 为 'sipp'，如有需要可调整
  const akun = await getSelectedAkunSippByTipe('sipp');
  if (akun) {
    USERNAME = akun.email;
    PASSWORD = akun.password;
  } else {
    USERNAME = null;
    PASSWORD = null;
  }
}

export async function login(page, attempt = 1) {
  const MAX_ATTEMPT = 5;

  try {
    // 取账号（如果还没设置则重新获取）
    if (!USERNAME || !PASSWORD) {
      await setSippCredentials();
    }
    console.log(`🔐 Login attempt #${attempt} ke ${LOGIN_URL}`);
    await safeGoto(page, LOGIN_URL);
    // await page.screenshot({ path: 'login_debug.png', fullPage: true });
    console.log("memulai proses captcha");
    const captchaText = await solveCaptchaByScreenshot(page,"sipp");
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
      captchaText,
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

      // 自动选择swal2-select的第一个有value的option并点击Ok按钮
      const hasSwalSelect = await page.$('select.swal2-select');
      if (hasSwalSelect) {
        await page.evaluate(() => {
          const select = document.querySelector('select.swal2-select');
          if (select) {
            const opts = Array.from(select.options).filter(opt => opt.value && !opt.disabled);
            if (opts.length > 0) {
              select.value = opts[0].value;
              select.dispatchEvent(new Event('change', { bubbles: true }));
            }
          }
        });
        // 点击Ok按钮
        const okBtn = await page.$('button.swal2-confirm');
        if (okBtn) await okBtn.click();
        // 等待modal消失
        await page.waitForSelector('select.swal2-select', { hidden: true, timeout: 5000 }).catch(()=>{});
      }

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
      }else{
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
