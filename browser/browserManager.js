// browserManager.js - Versi Single Browser
import puppeteer from "puppeteer";

// Global browser instance
let browser = null;
const pages = new Map(); // Menyimpan semua tab: jobId → page

// Konfigurasi default
const browserConfig = {
  headless: false,
  defaultViewport: null,
  userDataDir: './puppeteer_profile',
  executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  args: [
    "--no-sandbox",
    '--window-size=1366,768',
    '--force-device-scale-factor=1',
        "--disable-setuid-sandbox",
        "--disable-dev-shm-usage",
  ]
};

// Fungsi untuk mendapatkan/membuat browser
async function getBrowser() {
  if (browser && browser.isConnected()) {
    return browser;
  }

  // Jika browser disconnected, cleanup dulu
  if (browser) {
    try {
      await browser.close();
    } catch (error) {
      console.error('Error closing disconnected browser:', error);
    }
    browser = null;
    pages.clear();
  }

  console.log('🚀 Launching browser...');
  browser = await puppeteer.launch(browserConfig);

  console.log('dbrowser:', browser);
  
  browser.on('disconnected', () => {
    console.log('🔌 Browser disconnected');
    browser = null;
    pages.clear();
  });

  return browser;
}

export async function openTab(jobId, options = {}) {
  try {
    const browserInstance = await getBrowser();
    
    // Cek apakah tab sudah ada
    if (pages.has(jobId)) {
      const existingPage = pages.get(jobId);
      if (!existingPage.isClosed()) {
        console.log(`♻️ Reusing existing tab for jobId: ${jobId}`);
        return existingPage;
      }
      pages.delete(jobId);
    }

    console.log(`➕ Opening new tab for jobId: ${jobId}`);
    const page = await browserInstance.newPage();
    
    // Konfigurasi halaman
    // await page.setViewport(options.viewport || { width: 1366, height: 768 });
    // await page.setUserAgent(options.userAgent || 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    await page.setUserAgent(
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    );
    await page.setViewport({ width: 1366, height: 768 });
    
    
    // Simpan page
    pages.set(jobId, page);
    
    // Handler untuk ketika page ditutup
    page.on('close', () => {
      pages.delete(jobId);
      console.log(`🗑️ Tab ${jobId} closed`);
      
      // Jika tidak ada tab lagi, tutup browser setelah delay
      if (pages.size === 0) {
        setTimeout(async () => {
          if (pages.size === 0 && browser) {
            await closeBrowser();
          }
        }, 5000);
      }
    });

    return page;
  } catch (error) {
    console.error(`❌ Failed to open tab for ${jobId}:`, error);
    throw error;
  }
}

export async function closeTab(jobId) {
  const page = pages.get(jobId);
  if (!page) {
    console.log(`⚠️ Tab ${jobId} not found`);
    return;
  }

  try {
    await page.close();
    console.log(`✅ Tab ${jobId} closed successfully`);
  } catch (error) {
    console.error(`❌ Failed to close tab ${jobId}:`, error);
  }
}

export async function closeBrowser() {
  if (!browser) return;

  try {
    // Tutup semua tab terlebih dahulu
    for (const [jobId, page] of pages) {
      try {
        await page.close();
      } catch (error) {
        console.error(`Error closing tab ${jobId}:`, error);
      }
    }
    pages.clear();
    
    await browser.close();
    console.log('✅ Browser closed successfully');
  } catch (error) {
    console.error('❌ Failed to close browser:', error);
  } finally {
    browser = null;
  }
}

export function getBrowserStatus() {
  return {
    isConnected: browser ? browser.isConnected() : false,
    activeTabs: pages.size,
    tabIds: Array.from(pages.keys()),
    browserPid: browser ? browser.process().pid : null
  };
}
/**
 * Tutup semua tab yang masih terbuka.
 * @param {boolean} alsoCloseBrowser - kalau true, sekalian matikan browser.
 */
export async function closeAllTabs(alsoCloseBrowser = false) {
  if (pages.size === 0) {
    console.log('♻️  Tidak ada tab yang perlu ditutup');
  } else {
    console.log(`🗑️  Closing ${pages.size} tab(s)…`);
    for (const [jobId, page] of pages) {
      try {
        if (!page.isClosed()) await page.close();
      } catch (err) {
        console.error(`Error closing tab ${jobId}:`, err);
      }
    }
    pages.clear();
  }

  if (alsoCloseBrowser) await closeBrowser();
}

// Cleanup saat proses berhenti
process.on('exit', closeBrowser);
process.on('SIGINT', closeBrowser);
process.on('SIGTERM', closeBrowser);