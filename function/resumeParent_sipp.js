// runParentById.js - Improved Version
import { login } from "../function/sipp_login.js";
import { inputDataAndScrape } from "../function/sipp_scrape.js";
import { safeGoto } from "../function/config.js";
import {
  saveChild,
  updateStatusParent,
  checkChildStatus,
  getSelectedInduk
} from "../database/function.js";
import db from "../database/db.js";
import dotenv from "dotenv";
import { openTab, closeTab } from "../browser/browserManager.js";

dotenv.config();

const INPUT_PAGE_URL = process.env.SIPP_INPUT_URL;
const pad2 = (n) => n.toString().padStart(2, "0");

// Helper function to generate child KPJ numbers based on parent KPJ and z values
function generateChildKpj(parentKpj, z) {
  const prefix = parentKpj.toString().slice(0, -2); // Remove last 2 digits (zParent)
  return Number(`${prefix}${pad2(z)}`);
}

// Get the correct child_z array based on parent's z value
function getChildZValues(zParent) {
  const child_z = [
    [0, 18, 26, 34, 42, 59, 67, 75, 83, 91],
    [1, 19, 27, 35, 43, 50, 60, 68, 76, 84, 92],
    [2, 10, 28, 36, 44, 51, 69, 77, 85, 93],
    [3, 11, 29, 37, 45, 52, 60, 78, 86, 94],
    [4, 12, 20, 38, 46, 53, 61, 79, 87, 95],
    [5, 13, 21, 39, 47, 54, 62, 70, 88, 96],
    [6, 14, 22, 30, 48, 55, 63, 71, 89, 97],
    [7, 15, 23, 31, 49, 56, 64, 72, 80, 98],
    [8, 16, 24, 32, 40, 57, 65, 73, 81, 99],
    [9, 17, 25, 33, 41, 58, 66, 74, 82, 90],
  ];
  return child_z[zParent] || [];
}

export async function getParentById(id) {
  const [rows] = await db.query("SELECT * FROM parents WHERE id = ?", [id]);
  return rows[0] || null;
}

export async function getChildrenByParentId(parentId) {
  const [rows] = await db.query(`SELECT * FROM result WHERE parent_id = ?`, [
    parentId,
  ]);
  return rows;
}

export async function runParentById({ parentId, action = "start" }) {
  const jobId = `parent-${parentId}`;

  try {
    if (action === "stop") {
      await closeTab(jobId);
    }

    const parent = await getParentById(parentId);
    if (!parent) {
      throw new Error(`Parent with ID ${parentId} not found`);
    }

    const page = await openTab(jobId, {
      viewport: { width: 1920, height: 1080 },
      userAgent:
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
    });

    // Login if needed
    if (page.__logged !== true) {
      await login(page);
      page.__logged = true;
    }

    // Extract zParent from the last 2 digits of KPJ
    const zParent = parseInt(parent.kpj.toString().slice(-2));
    const child_z = getChildZValues(zParent);

    // Process all possible children (both existing and new)
    for (const z of child_z) {
      const childKpj = generateChildKpj(parent.kpj, z);

      // Check if child already exists in database
      const hasCheckedChild = await checkChildStatus(childKpj);
      if (hasCheckedChild === "success") {
        console.log(`✅ Child ${childKpj} already processed`);
        continue;
      }

      console.log(`🔍 Processing child KPJ: ${childKpj}`);
      await safeGoto(page, INPUT_PAGE_URL);

      const childResult = await inputDataAndScrape(page, { kpj: childKpj });

      // Save results with appropriate status
      const resultToSave = {
        ...childResult,
        kpj: childKpj,
        sipp_status: childResult.keterangan ? "success" : "pending",
        parent_id: parentId,
      };

      await saveChild(resultToSave, parentId);
      console.log(
        `💾 Saved child ${childKpj} with status: ${resultToSave.sipp_status}`
      );

      await new Promise((resolve) => setTimeout(resolve, 2000)); // Delay between requests
    }

    // Update parent status after all children processed
    await updateStatusParent(parentId, "success");
    console.log(
      `✅ Successfully processed all children for parent ${parent.kpj}`
    );

    await closeTab(jobId);
    return {
      success: true,
      message: `Completed processing for parent ${parent.kpj}`,
    };
  } catch (error) {
    console.error(`❌ Error processing parent ${parentId}:`, error);
    await closeTab(jobId);
    throw error;
  }
}
