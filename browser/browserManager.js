// browserManager.js - Versi yang Diperbaiki
import puppeteer from "puppeteer";
import { v4 as uuidv4 } from 'uuid';

// Struktur untuk menyimpan instance browser
const browserInstances = new Map();

// Konfigurasi default
const defaultConfig = {
  headless: false,
  userDataDir: `./puppeteer_profile_${uuidv4()}`,
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  args: [
    '--no-sandbox',
    '--disable-dev-shm-usage',
    '--disable-web-security',
    '--disable-features=site-per-process',
    '--disable-setuid-sandbox'
  ]
};

// Fungsi untuk mendapatkan atau membuat browser instance
async function getBrowserInstance(instanceId = 'default') {
  if (browserInstances.has(instanceId)) {
    const instance = browserInstances.get(instanceId);
    if (instance.browser && instance.browser.isConnected()) {
      return instance;
    }
    // Cleanup jika browser disconnected
    browserInstances.delete(instanceId);
  }

  console.log(`🚀 Membuat browser instance baru: ${instanceId}`);
  const browser = await puppeteer.launch({
    ...defaultConfig,
    userDataDir: `./puppeteer_profile_${instanceId}`
  });

  const newInstance = {
    browser,
    pages: new Map(),
    instanceId
  };

  browser.on('disconnected', () => {
    console.log(`🔌 Browser instance ${instanceId} disconnected`);
    browserInstances.delete(instanceId);
  });

  browserInstances.set(instanceId, newInstance);
  return newInstance;
}

export async function openTab(jobId, options = {}) {
  const instanceId = options.instanceId || 'default';
  
  try {
    const instance = await getBrowserInstance(instanceId);
    
    // Cek apakah tab sudah ada
    if (instance.pages.has(jobId)) {
      const existingPage = instance.pages.get(jobId);
      if (!existingPage.isClosed()) {
        console.log(`♻️ Menggunakan tab yang sudah ada untuk jobId: ${jobId}`);
        return existingPage;
      }
      instance.pages.delete(jobId);
    }

    console.log(`➕ Membuka tab baru untuk jobId: ${jobId}`);
    const page = await instance.browser.newPage();
    
    // Konfigurasi halaman
    await page.setViewport(options.viewport || { width: 1366, height: 768 });
    await page.setUserAgent(options.userAgent || 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    // Simpan page ke instance
    instance.pages.set(jobId, page);
    
    // Handler untuk ketika page ditutup
    page.on('close', () => {
      instance.pages.delete(jobId);
      console.log(`🗑️ Tab ${jobId} ditutup`);
    });

    return page;
  } catch (error) {
    console.error(`❌ Gagal membuka tab untuk ${jobId}:`, error);
    throw error;
  }
}

export async function closeTab(jobId, instanceId = 'default') {
  const instance = browserInstances.get(instanceId);
  if (!instance) {
    console.log(`⚠️ Instance ${instanceId} tidak ditemukan`);
    return;
  }

  const page = instance.pages.get(jobId);
  if (!page) {
    console.log(`⚠️ Tab ${jobId} tidak ditemukan`);
    return;
  }

  try {
    await page.close();
    console.log(`✅ Tab ${jobId} berhasil ditutup`);
  } catch (error) {
    console.error(`❌ Gagal menutup tab ${jobId}:`, error);
  }
}

export async function closeBrowser(instanceId = 'default') {
  const instance = browserInstances.get(instanceId);
  if (!instance) return;

  try {
    await instance.browser.close();
    console.log(`✅ Browser instance ${instanceId} berhasil ditutup`);
  } catch (error) {
    console.error(`❌ Gagal menutup browser instance ${instanceId}:`, error);
  } finally {
    browserInstances.delete(instanceId);
  }
}

// Fungsi untuk mendapatkan status browser
export function getBrowserStatus() {
  const status = {};
  for (const [instanceId, instance] of browserInstances) {
    status[instanceId] = {
      isConnected: instance.browser.isConnected(),
      activeTabs: instance.pages.size,
      tabIds: Array.from(instance.pages.keys())
    };
  }
  return status;
}