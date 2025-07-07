// lasikScraper.js
import dotenv from "dotenv";
import { safeGoto } from "../function/config.js";
import { updateLasikStatus, checkLasikStatus } from '../database/function.js';
import { openTab, closeTab } from "../browser/browserManager.js";
import db from "../database/db.js";

dotenv.config();

const LASIK_URL = process.env.LASIK_URL || "https://lapakasik.bpjsketenagakerjaan.go.id/";

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

export async function getPendingLasikChildren() {
    const [rows] = await db.query(
        `SELECT * FROM result 
         WHERE lasik_status IS NULL OR lasik_status NOT IN ('success')`
    );
    return rows;
}

// Main scraping function
async function scrapeSingleLasik(page, childData, attempt = 1) {
    const { id: childId, kpj, nik, nama_lengkap, tgl_lahir, parent_id } = childData;
    
    // Check if already processed
    const checkStatus = await checkLasikStatus(childId);
    if (checkStatus === "success") {
        console.log(`✅ LASIK data already complete for KPJ: ${kpj}`);
        return { status: "already_complete", childId, kpj };
    }

    try {
        await safeGoto(page, LASIK_URL, { waitUntil: "networkidle2" });
        
        // Fill NIK field
        await page.waitForSelector('input[placeholder="Isi Nomor E-KTP"]', { timeout: 10000 });
        await page.$eval('input[placeholder="Isi Nomor E-KTP"]', (el, nik) => {
            el.value = nik;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }, nik);

        await new Promise(resolve => setTimeout(resolve, 1000));

        // Fill KPJ field
        await page.$eval('input[placeholder="Isi Nomor KPJ"]', (el, kpj) => {
            el.value = kpj;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }, kpj);

        await new Promise(resolve => setTimeout(resolve, 1000));

        // Fill Name field
        await page.$eval('input[placeholder="Isi Nama sesuai KTP"]', (el, name) => {
            el.value = name;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }, nama_lengkap);

        await new Promise(resolve => setTimeout(resolve, 2000));

        // Check for modal (might appear automatically)
        let modalContent = await checkForModal(page);
        
        // If no modal, click next button
        if (!modalContent) {
            await page.click('#nextBtn');
            await new Promise(resolve => setTimeout(resolve, 3500));
            modalContent = await checkForModal(page);
        }

        // Determine status from modal content
        let status = 'not_found';
        if (modalContent) {
            if (modalContent.includes('JMO')) status = 'jmo';
            else if (modalContent.includes('15 juta') || modalContent.includes('15jt')) status = 'lebih_15jt';
            else if (modalContent.includes('masih aktif')) status = 'aktif';
            else if (modalContent.includes('Kantor Cabang')) status = 'cabang';
            else {
                status = 'not_found';
            }
        }

        // Update database
        await updateLasikStatus(childId, status, 'success');
        
        return { 
            status: "success",
            childId,
            kpj,
            lasikStatus: status
        };
        
    } catch (error) {
        console.error(`❌ Error scraping LASIK for KPJ ${kpj}:`, error.message);
        
        // Update with error status
        const finalStatus = attempt >= 3 ? 'failed' : 'error';
        await updateLasikStatus(childId, finalStatus, finalStatus);
        
        return { 
            status: "error",
            error: error.message,
            attempts: attempt,
            childId,
            kpj
        };
    }
}

async function checkForModal(page) {
    try {
        return await page.evaluate(() => {
            const modal = document.querySelector('.swal2-modal');
            if (modal) {
                const content = modal.querySelector('.swal2-content');
                return content ? content.innerText : null;
            }
            return null;
        });
    } catch (error) {
        console.error("Error checking modal:", error);
        return null;
    }
}

export async function scrapeLasik({ data, action = 'start', type = "child" }) {
    let jobId;
    let childId = data?.childId;
    let parentId = data?.parentId;
    
    // Determine job ID based on type
    if (type === "parent") {
        if (!parentId) throw new Error("parentId is required for parent type");
        jobId = `lasik-parent-${parentId}`;
    } 
    else if (type === "child") {
        if (!childId) throw new Error("childId is required for child type");
        jobId = `lasik-child-${childId}`;
    } 
    else {
        jobId = `lasik-all`;
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
            
            console.log(`🔍 Processing LASIK for child ${childId} (KPJ: ${child.kpj})`);
            const result = await scrapeSingleLasik(page, child);
            results.push(result);
        } 
        else if (type === "parent") {
            // All children of a parent
            const children = await getChildrenByParentId(parentId);
            if (!children.length) throw new Error(`No children found for parent ${parentId}`);
            
            console.log(`🔍 Processing LASIK for ${children.length} children of parent ${parentId}`);
            
            for (const child of children) {
                const result = await scrapeSingleLasik(page, child);
                results.push(result);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Delay between children
            }
        } 
        else {
            // "all" mode - processes all pending LASIK records
            const children = await getPendingLasikChildren();
            if (!children.length) {
                console.log("No pending LASIK records found");
                return { status: "completed", message: "No pending LASIK records found" };
            }
            
            console.log(`🔍 Processing ${children.length} pending LASIK records`);
            
            for (const child of children) {
                const result = await scrapeSingleLasik(page, child);
                results.push(result);
                await new Promise(resolve => setTimeout(resolve, 1000)); // Delay between records
            }
        }
        
        await closeTab(jobId);
        
        // Count results
        const successCount = results.filter(r => r.status === "success").length;
        const alreadyCount = results.filter(r => r.status === "already_complete").length;
        const errorCount = results.filter(r => r.status === "error").length;
        
        console.log(`✅ Completed LASIK scraping: ${successCount} succeeded, ${alreadyCount} already complete, ${errorCount} failed`);
        
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
        console.error(`❌ Error in LASIK scraping (${type} mode):`, err);
        await closeTab(jobId);
        throw err;
    }
}