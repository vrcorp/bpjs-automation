import dotenv from "dotenv";
import { safeGoto } from "./config.js";
import { updateDPT, checkDPTStatus } from './database/function.js';
import db from "../database/db.js";
dotenv.config();
import { openTab, closeTab } from "../browser/browserManager.js";

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
async function scrapeSingleDpt(page, nik, parentId, attempt = 1) {
    const checkStatus = await checkDPTStatus(nik, parentId);
    
    if(checkStatus === "success"){
        console.log(`✅ DPT data already complete for NIK: ${nik}`);
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

        const searchButton = await page.$x("//button[span[contains(text(),'Pencarian')]]");
        if (searchButton.length === 0) throw new Error("Search button not found");
        await searchButton[0].click();

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
        return { 
            status: "success",
            data: result 
        };
        
    } catch (error) {
        result.dpt_status = attempt >= 3 ? 'failed' : 'error';
        await updateDPT(result, parentId);
        
        console.error(`❌ Error scraping DPT for NIK ${nik}:`, error.message);
        
        // Retry logic (max 3 attempts)
        if (attempt < 3) {
            console.log(`🔄 Retrying (attempt ${attempt + 1})`);
            await new Promise(resolve => setTimeout(resolve, 2000));
            return scrapeSingleDpt(page, nik, parentId, attempt + 1);
        }
        
        return { 
            status: "error",
            error: error.message,
            attempts: attempt,
            nik
        };
    }
}

export async function scrapeDpt({ data, action = 'start', type = "all" }) {
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
            const result = await scrapeSingleDpt(page, child.nik, child.parent_id);
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