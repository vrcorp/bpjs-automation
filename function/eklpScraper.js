// eklpScraper.js
import dotenv from "dotenv";
import { solveCaptchaByScreenshot } from '../function/captcha_solver.js';
import { safeGoto } from '../function/config.js';
import { updateEklpStatus, checkEklpStatus } from '../database/function.js';
import { openTab, closeTab } from "../browser/browserManager.js";
import db from "../database/db.js";

dotenv.config();

const EKLP_LOGIN_URL = process.env.EKLP_LOGIN_URL || "https://e-plkk.bpjsketenagakerjaan.go.id/login.bpjs";
const EKLP_INPUT_URL = process.env.EKLP_INPUT_URL || "https://e-plkk.bpjsketenagakerjaan.go.id/form/eligble.bpjs";
const EKLP_USERNAME = process.env.EKLP_USERNAME;
const EKLP_PASSWORD = process.env.EKLP_PASSWORD;

// Database functions
export async function getChildById(childId) {
    const [rows] = await db.query("SELECT * FROM result WHERE id = ?", [childId]);
    return rows[0] || null;
}

export async function getChildrenByParentId(parentId) {
    const [rows] = await db.query(
        `SELECT * FROM result WHERE parent_id = ?`,
        [parentId]
    );
    return rows;
}

export async function getPendingEklpChildren() {
    const [rows] = await db.query(
        `SELECT * FROM result 
         WHERE eklp_status IS NULL OR eklp_status NOT IN ('success')`
    );
    return rows;
}

// Login function
export async function loginEklp(page, attempt = 1) {
    const MAX_ATTEMPT = 5;

    try {
        console.log(`🔐 EKLP Login attempt #${attempt}`);
        await safeGoto(page, EKLP_LOGIN_URL);
        
        // Check if already logged in
        if (page.url().includes('/dashboard.bpjs')) return true;

        // Solve captcha
        const captchaText = await solveCaptchaByScreenshot(page, "eklp");
        if (!captchaText) {
            throw new Error("Failed to solve captcha");
        }

        // Fill login form
        await page.type('#emailppk', EKLP_USERNAME);
        await page.type('#pass', EKLP_PASSWORD);
        await page.type('#captcha', captchaText);
        
        // Submit form
        await Promise.all([
            page.click('input[name="submit"]'),
            page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 })
                .catch(() => console.log("Navigation timeout"))
        ]);

        await page.waitForNavigation({ timeout: 5000 }).catch(() => {});
      
        if (!page.url().includes('/dashboard.bpjs')) {
            throw new Error('Gagal login (masih di halaman login, captcha mungkin salah)');
        }

        console.log("✅ EKLP Login successful");
        return true;

    } catch (err) {
        console.error(`❌ EKLP Login attempt #${attempt} failed:`, err.message);
        
        if (attempt < MAX_ATTEMPT) {
            await new Promise(resolve => setTimeout(resolve, 5000));
            return loginEklp(page, attempt + 1);
        }
        
        throw new Error("Max login attempts reached");
    }
}

// Main scraping function
async function scrapeSingleEklp(page, childData, attempt = 1) {
    const { id: childId, kpj, nik, parent_id } = childData;
    
    // Check if already processed
    const checkStatus = await checkEklpStatus(childId);
    if (checkStatus === "success") {
        console.log(`✅ EKLP data already complete for KPJ: ${kpj}`);
        return { status: "already_complete", childId, kpj };
    }

    try {
        await safeGoto(page, EKLP_INPUT_URL, { waitUntil: "networkidle2" });
        
        // Fill KPJ/NIK field (using KPJ if available, otherwise NIK)
        // 打印 childData 以便调试
        console.log("EKLP 子对象信息:", childData);
        const identifier = nik || kpj;
        console.log('identiifer',identifier);
        await page.type('#kpj', identifier);
        
        // Set current date for accident date
        // 日期格式：09-07-2025
        // 使日期为印尼西部时间（WIB, UTC+7）
        const nowUtc = new Date();
        // 转换为WIB时区
        const wibOffset = 7 * 60; // 分钟
        const local = new Date(nowUtc.getTime() + (wibOffset - nowUtc.getTimezoneOffset()) * 60000);
        const dd = String(local.getDate()).padStart(2, '0');
        const mm = String(local.getMonth() + 1).padStart(2, '0');
        const yyyy = local.getFullYear();
        const todayStr = `${dd}-${mm}-${yyyy}`; // 例如：09-07-2025

        await page.$eval('#tgl', (el, date) => {
            el.value = date;
        }, todayStr);
        
        // Set current time for accident time (format: HH:MM)
        // 按照要求，时间格式为 23:21
        // 修正：使用 local 变量（已定义为当前WIB时间）
        // 将 WIB 时间减去 1 小时
        let adjustedHour = local.getHours() - 1;
        if (adjustedHour < 0) adjustedHour = 23; // 防止负数，回绕到23点
        const hours = String(adjustedHour).padStart(2, '0');
        const minutes = String(local.getMinutes()).padStart(2, '0');
        const timeStr = `${hours}:${minutes}`; // 例如：22:21
        
        await page.type('#jamKecelakaan', timeStr);
        
        // Submit form
        await Promise.all([
            page.click('button[type="submit"]'),
            page.waitForNavigation({ waitUntil: "networkidle0", timeout: 10000 })
                .catch(() => console.log("Navigation timeout"))
        ]);
        
        // Check result
        const result = await page.evaluate(() => {
            const hasilDiv = document.getElementById('hasil');
            if (hasilDiv) {
                const innerText = hasilDiv.innerText || hasilDiv.textContent;
                if (innerText.includes("Peserta Layak")) return "aktif";
                if (innerText.includes("belum dapat dilakukan")) return "tidak_aktif";
            }
            return "not_found";
        });
        
        // Update database
        await updateEklpStatus(childId, result, 'success');
        
        return { 
            status: "success",
            childId,
            kpj: identifier,
            eklpStatus: result
        };
        
    } catch (error) {
        console.error(`❌ Error scraping EKLP for KPJ ${kpj || nik}:`, error.message);
        
        // Update with error status
        const finalStatus = attempt >= 3 ? 'failed' : 'error';
        await updateEklpStatus(childId, finalStatus, finalStatus);
        
        
        return { 
            status: "error",
            error: error.message,
            attempts: attempt,
            childId,
            kpj: kpj || nik
        };
    }
}

export async function scrapeEklp({ data, action = 'start', type = "child" }) {
    let jobId;
    let childId = data?.childId;
    let parentId = data?.parentId;
    
    // Determine job ID based on type
    if (type === "parent") {
        if (!parentId) throw new Error("parentId is required for parent type");
        jobId = `eklp-parent-${parentId}`;
    } 
    else if (type === "child") {
        if (!childId) throw new Error("childId is required for child type");
        jobId = `eklp-child-${childId}`;
    } 
    else {
        jobId = `eklp-all`;
    }
    
    try {
        // Handle stop action
        if (action === "stop") {
            await closeTab(jobId);
            return { status: "stopped", jobId };
        }
        
        // Start scraping
        const page = await openTab(jobId, {
            viewport: { width: 1366, height: 768 },
            userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
        });

        // Login first
        await loginEklp(page);

        let results = [];
        
        if (type === "child") {
            // Single child mode
            const child = await getChildById(childId);
            if (!child) throw new Error(`Child with ID ${childId} not found`);
            
            console.log(`🔍 Processing EKLP for child ${childId} (KPJ: ${child.kpj || child.nik})`);
            const result = await scrapeSingleEklp(page, child);
            results.push(result);
        } 
        else if (type === "parent") {
            // All children of a parent
            const children = await getChildrenByParentId(parentId);
            if (!children.length) throw new Error(`No children found for parent ${parentId}`);
            
            console.log(`🔍 Processing EKLP for ${children.length} children of parent ${parentId}`);
            
            for (const child of children) {
                const result = await scrapeSingleEklp(page, child);
                results.push(result);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Delay between children
            }
        } 
        else {
            // "all" mode - processes all pending EKLP records
            const children = await getPendingEklpChildren();
            if (!children.length) {
                console.log("No pending EKLP records found");
                return { status: "completed", message: "No pending EKLP records found" };
            }
            
            console.log(`🔍 Processing ${children.length} pending EKLP records`);
            
            for (const child of children) {
                const result = await scrapeSingleEklp(page, child);
                results.push(result);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Delay between records
            }
        }
        
        await closeTab(jobId);
        
        // Count results
        const successCount = results.filter(r => r.status === "success").length;
        const alreadyCount = results.filter(r => r.status === "already_complete").length;
        const errorCount = results.filter(r => r.status === "error").length;
        
        console.log(`✅ Completed EKLP scraping: ${successCount} succeeded, ${alreadyCount} already complete, ${errorCount} failed`);
        
        return {
            status: "completed",
            type,
            parentId: type === "parent" ? parentId : undefined,
            childId: type === "child" ? childId : undefined,
            results: {
                total: results.length,
                success: successCount,
                already_complete: alreadyCount,
                errors: errorCount
            }
        };
        
    } catch (err) {
        console.error(`❌ Error in EKLP scraping (${type} mode):`, err);
        await closeTab(jobId);
        throw err;
    }
}