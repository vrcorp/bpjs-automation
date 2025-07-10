import dotenv from "dotenv";
import { safeGoto } from "../function/config.js";
import { updateDPT, checkDPTStatus } from '../database/function.js';
import db from "../database/db.js";
dotenv.config();
import { openTab, closeTab } from "../browser/browserManager.js";
import {sendTelegramNotif} from "../function/telegram-notif.js";


const URL_DPT = process.env.DPT_URL;

// Database Functions
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

export async function getAllChild() {
    const [rows] = await db.query(
        `SELECT * FROM result`
    );
    return rows;
}

export async function getPendingDptChildren() {
    const [rows] = await db.query(
        `SELECT * FROM result 
         WHERE dpt_status IS NULL OR dpt_status NOT IN ('success')`
    );
    return rows;
}

// Scraping Functions
async function scrapeSingleDpt(page, nik, parentId, attempt = 1 , mode) {
    const checkStatus = await checkDPTStatus(nik, parentId);
    
    if(checkStatus === "success"){
        console.log(`✅ DPT data already complete for NIK: ${nik}`);
        const [rows] = await db.query(`
            SELECT r.nik, r.kpj, r.nama, r.email, r.hp, r.ttl,
                   r.kota, r.kecamatan, r.kelurahan,      -- <─ tambahkan
                   r.notif_lasik, r.notif_eklp            -- <─ tambahkan
            FROM result r
            JOIN parents p ON r.parent_id = p.id
            WHERE r.nik = ? AND r.parent_id = ?
          `, [nik, parentId]);
          
          const latestDptData = rows[0];      // objek baris pertama
          if (!latestDptData) {
            console.error('❌ Data DPT tidak ditemukan');
            return { status: 'not_found', nik };
          }
          
        
        if (latestDptData) {
            let pesan = `
            <b>🔔 Notifikasi Pembaruan Data DPT</b>

            <b>Mode:</b> ${mode || 'N/A'}
            <b>NIK:</b> ${latestDptData.nik || 'N/A'}
            <b>KPJ:</b> ${latestDptData.kpj || 'N/A'}
            <b>Nama:</b> ${latestDptData.nama || 'N/A'}
            <b>Email:</b> ${latestDptData.email || 'N/A'}
            <b>HP:</b> ${latestDptData.hp || 'N/A'}
            <b>TTL:</b> ${latestDptData.ttl || 'N/A'}
            <b>Kota:</b> ${latestDptData.kota || 'N/A'}
            <b>Kecamatan:</b> ${latestDptData.kecamatan || 'N/A'}
            <b>Kelurahan:</b> ${latestDptData.kelurahan || 'N/A'}
            `;

            if (mode === 'sipp_lasik_dpt') {
            pesan += `<b>Lasik:</b> ${latestDptData.notif_lasik || 'N/A'}\n`;
            } else if (mode === 'sipp_eklp_dpt') {
            pesan += `<b>EKLP:</b> ${latestDptData.notif_eklp || 'N/A'}\n`;
            }

            pesan = pesan.trim();

            
            try {
                await sendTelegramNotif(process.env.TARGET_USER_ID, pesan);
                console.log(`📱 Telegram通知已发送 - NIK: ${nik}`);
            } catch (telegramError) {
                console.error(`❌ Telegram通知发送失败:`, telegramError.message);
            }
        }
        return { status: "already_complete", nik };
    }

    let result = {
        kota: "",
        kecamatan: "",
        kelurahan: "",
        percobaan_dpt: attempt,
        dpt_status: 'processing',
        nik: nik,
    };
    
    await updateDPT(result, parentId);
    
    try {
        await safeGoto(page, URL_DPT, { waitUntil: "networkidle2" });
        await page.waitForSelector("form", { timeout: 10000 });
        
        const firstTextInput = await page.$('form input[type="text"]');
        if (!firstTextInput) throw new Error("Input field not found");
        
        await firstTextInput.click({ clickCount: 3 });
        await firstTextInput.type(nik || "", { delay: 50 });

        await page.waitForSelector('div.wizard-buttons button'); // kontainer tombol

        const clicked = await page.evaluate(() => {
        const btns = [...document.querySelectorAll('div.wizard-buttons button')];
        const target = btns.find(b =>
            b.textContent.trim().includes('Pencarian')
        );
        if (target) {
            target.click();
            return true;
        }
        return false;
        });

        if (!clicked) throw new Error('Tombol Pencarian nggak ketemu');



        await page.waitForFunction(
            () => {
                const kab = document.querySelector(".row.row-3 p.row--left span");
                const kec = document.querySelector(".row.row-3 p.row--center span");
                const kel = document.querySelector(".row.row-3 p.row--right span");
                return kab && kec && kel;
            },
            { timeout: 10000 }
        );

        const wilayah = await page.evaluate(() => {
            const kab = document.querySelector(".row.row-3 p.row--left");
            const kec = document.querySelector(".row.row-3 p.row--center");
            const kel = document.querySelector(".row.row-3 p.row--right");
            return {
                kabupaten: kab ? kab.childNodes[1]?.textContent?.trim() : null,
                kecamatan: kec ? kec.childNodes[1]?.textContent?.trim() : null,
                kelurahan: kel ? kel.childNodes[1]?.textContent?.trim() : null,
            };
        });

        // Update successful result
        result.kota = wilayah.kabupaten;
        result.kecamatan = wilayah.kecamatan;
        result.kelurahan = wilayah.kelurahan;
        result.dpt_status = 'success';
        
        await updateDPT(result, parentId);
        // 获取最新的DPT数据并发送Telegram通知
        const [rows] = await db.query(`
            SELECT r.nik, r.kpj, r.nama, r.email, r.ttl, r.hp,
                   r.kota, r.kecamatan, r.kelurahan,      -- <─ tambahkan
                   r.notif_lasik, r.notif_eklp            -- <─ tambahkan
            FROM result r
            JOIN parents p ON r.parent_id = p.id
            WHERE r.nik = ? AND r.parent_id = ?
          `, [nik, parentId]);
          
          const latestDptData = rows[0];      // objek baris pertama
          if (!latestDptData) {
            console.error('❌ Data DPT tidak ditemukan');
            return { status: 'not_found', nik };
          }
          
        
        if (latestDptData) {
            let pesan = `
            <b>🔔 Notifikasi Pembaruan Data DPT</b>

            <b>Mode:</b> ${mode || 'N/A'}
            <b>NIK:</b> ${latestDptData.nik || 'N/A'}
            <b>KPJ:</b> ${latestDptData.kpj || 'N/A'}
            <b>Nama:</b> ${latestDptData.nama || 'N/A'}
            <b>Email:</b> ${latestDptData.email || 'N/A'}
            <b>HP:</b> ${latestDptData.hp || 'N/A'}
            <b>TTL:</b> ${latestDptData.ttl || 'N/A'}
            <b>Kota:</b> ${latestDptData.kota || 'N/A'}
            <b>Kecamatan:</b> ${latestDptData.kecamatan || 'N/A'}
            <b>Kelurahan:</b> ${latestDptData.kelurahan || 'N/A'}
            `;

            if (mode === 'sipp_lasik_dpt') {
            pesan += `<b>Lasik:</b> ${latestDptData.notif_lasik || 'N/A'}\n`;
            } else if (mode === 'sipp_eklp_dpt') {
            pesan += `<b>EKLP:</b> ${latestDptData.notif_eklp || 'N/A'}\n`;
            }

            pesan = pesan.trim();

            
            try {
                await sendTelegramNotif(process.env.TARGET_USER_ID, pesan);
                console.log(`📱 Telegram通知已发送 - NIK: ${nik}`);
            } catch (telegramError) {
                console.error(`❌ Telegram通知发送失败:`, telegramError.message);
            }
        }
        return { 
            status: "success",
            data: result 
        };
        
    } catch (error) {
        // if (attempt >= 3) throw error;
        result.dpt_status = 'error';
        await updateDPT(result, parentId);
        try {
            await sendTelegramNotif(process.env.TARGET_USER_ID, pesan);
            console.log(`📱 Telegram通知已发送 - NIK: ${nik}`);
        } catch (telegramError) {
            console.error(`❌ Telegram通知发送失败:`, telegramError.message);
        }
        console.error(`❌ Error scraping DPT for NIK ${nik}:`, error.message);
        
        // Retry logic (max 3 attempts)
        // if (attempt < 3) {
        //     console.log(`🔄 Retrying (attempt ${attempt + 1})`);
        //     await new Promise(resolve => setTimeout(resolve, 2000));
        //     return scrapeSingleDpt(page, nik, parentId, attempt + 1);
        // }
        
        return { 
            status: "error",
            error: error.message,
            attempts: attempt,
            nik
        };
    }
}

export async function scrapeDpt({ data, action = 'start', type = "all", mode }) {
    let jobId;
    let childId = data?.childId;
    let parentId = data?.parentId;
    
    // Determine job ID based on type
    if (type === "parent") {
        if (!parentId) throw new Error("parentId is required for parent type");
        jobId = `dpt-parent-${parentId}`;
    } 
    else if (type === "child") {
        if (!childId) throw new Error("childId is required for child type");
        jobId = `dpt-child-${childId}`;
    } 
    else {
        jobId = `dpt-all`;
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

        let results = [];
        
        if (type === "child") {
            // Single child mode
            const child = await getChildById(childId);
            if (!child) throw new Error(`Child with ID ${childId} not found`);
            
            console.log(`🔍 Processing DPT for child ${childId} (NIK: ${child.nik})`);
            const result = await scrapeSingleDpt(page, child.nik, child.parent_id,null,mode);
            results.push(result);
        } 
        else if (type === "parent") {
            // All children of a parent
            const children = await getChildrenByParentId(parentId);
            if (!children.length) throw new Error(`No children found for parent ${parentId}`);
            
            console.log(`🔍 Processing DPT for ${children.length} children of parent ${parentId}`);
            
            for (const child of children) {
                const result = await scrapeSingleDpt(page, child.nik, parentId);
                results.push(result);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Delay between children
            }
        } 
        else {
            // "all" mode - processes all pending DPT records
            const children = await getPendingDptChildren();
            if (!children.length) {
                console.log("No pending DPT records found");
                return { status: "completed", message: "No pending DPT records found" };
            }
            
            console.log(`🔍 Processing ${children.length} pending DPT records`);
            
            for (const child of children) {
                const result = await scrapeSingleDpt(page, child.nik, child.parent_id);
                results.push(result);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Delay between records
            }
        }
        
        await closeTab(jobId);
        
        // Count results
        const successCount = results.filter(r => r.status === "success").length;
        const alreadyCount = results.filter(r => r.status === "already_complete").length;
        const errorCount = results.filter(r => r.status === "error").length;
        
        console.log(`✅ Completed DPT scraping: ${successCount} succeeded, ${alreadyCount} already complete, ${errorCount} failed`);
        
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
        console.error(`❌ Error in DPT scraping (${type} mode):`, err);
        await closeTab(jobId);
        throw err;
    }
}
