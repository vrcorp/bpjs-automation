import { generateSipp } from "./generateSipp.js";
import { scrapeLasik } from "./lasikScrape.js";
import { scrapeEklp } from "./eklpScraper.js";
import { scrapeDpt } from "./dptScrape.js";
import { closeAllTabs } from "../browser/browserManager.js";
import db from "../database/db.js";

/**
 * mode: 'sipp_lasik_dpt' | 'sipp_eklp_dpt' | 'sipp_dpt'
 * type: 'parent' | 'all' | 'child' (default parent)
 * parentId: 可选，指定 parent
 */
export async function generateAll({
  mode = "sipp_lasik_dpt",
  type = "parent",
  parentId = null,
  is_file = false,
  action = "start",
} = {}) {
  // 如果 action 是 'start'，需要检查是否有相同 mode 但 parentId 不同的正在运行的作业
  if (action === "start") {
    // 查询是否有同 mode 但 parentId 不同且状态为 pending 或 process 的作业
    const [runningJobs] = await db.query(
      'SELECT * FROM running_jobs WHERE status IN ("pending", "process")'
    );
    if (runningJobs.length > 0) {
      // 停止这些作业
      await db.query(
        'UPDATE running_jobs SET status = "finish", end_at = NOW() WHERE id IN (?)',
        [runningJobs.map((job) => job.id)]
      );
      // 这里可以加关闭浏览器等清理逻辑（如果有 browser 实例管理的话）
      // 例如: await closeAllBrowsersForJobs(runningJobs);
      await closeAllTabs(true);
      console.log(
        `已停止 ${runningJobs.length} 个 parentId 不同的正在运行的作业`
      );
    }
  }

  if (action === "stop") {
    // 停止所有正在运行的作业
    await db.query(
      'UPDATE running_jobs SET status = "finish", end_at = NOW() WHERE status IN ("pending", "process") AND mode = ?',
      [mode]
    );
    return { status: "stopped" };
  }

  // 创建新的运行作业记录
  const [jobResult] = await db.query(
    'INSERT INTO running_jobs (mode, parent_id, status, is_file) VALUES (?, ?, "pending", ?)',
    [mode, parentId, is_file]
  );
  const jobId = jobResult.insertId;

  try {
    // 更新作业状态为处理中
    await db.query('UPDATE running_jobs SET status = "process" WHERE id = ?', [
      jobId,
    ]);

    console.log(
      `🚀 开始执行 generateAll - Mode: ${mode}, Type: ${type}, ParentId: ${
        parentId || "all"
      }`
    );
    // 1. 先跑 SIPP（同步，确保数据库已更新）
    await generateSipp({
      mode: "default",
      action: action,
      is_file: is_file,
      parentId: parentId,
    });

    // 2. 查询所有 parent（或指定 parent）
    let parentRows = [];

    if (parentId) {
      const [rows] = await db.query("SELECT * FROM parents WHERE id = ?", [
        parentId,
      ]);

      if (rows.length === 0) {
        return { status: "error", message: "Parent not found" };
      }

      parentRows = rows; // JANGAN dibungkus lagi!
    } else {
      const [rows] = await db.query(
        "SELECT * FROM parents WHERE is_file = ?",
        [is_file ? 1 : 0] // 1 = TRUE, 0 = FALSE
      );
      parentRows = rows;
    }

    console.log(mode);
    for (const parent of parentRows) {
      // 3. 查询所有符合条件的 child
      const sql = `
        SELECT *
        FROM result
        WHERE parent_id = ?
            AND nik IS NOT NULL
            AND TRIM(nik) <> ''
            AND (
            notif_sipp = 'Sukses'
            OR notif_sipp = 'Tidak bisa digunakan'
            )
        `;

      const [children] = await db.query(sql, [parent.id]);
      console.log("parent id", parent.id);
      console.log(children.length);
      console.log("children 数据:", children);
      if (!children.length) continue;

      // 4. 根据 mode 依次处理
      if (mode === "sipp_lasik_dpt") {
        // LASIK
        // 1️⃣  Start rangkaian LASIK (serial)
        // await Promise.all(
        //   children.map(async (child) => {
        //     await scrapeLasik({
        //       data: { childId: child.id },
        //       action,
        //       type: "child",
        //       mode,
        //     });

        //     await scrapeDpt({
        //       data: { childId: child.id, parentId: parent.id },
        //       action,
        //       type: "child",
        //       mode,
        //     });
        //   })
        // );
        const dptPromises = [];

        await Promise.all(
            children.map(async (child) => {
                await scrapeLasik({
                    data: { childId: child.id },
                    action,
                    type: "child",
                    mode,
                });
                dptPromises.push(scrapeDpt({
                    data: { childId: child.id, parentId: parent.id },
                    action,
                    type: "child",
                    mode,
                }));
            })
        );

        await Promise.all(dptPromises);

        console.log("Semua job (Lasik→DPT) tiap child beres!");

        // 4️⃣  Lanjut ke bawah...
        console.log("Semua LASIK & DPT rampung!");
      } else if (mode === "sipp_eklp_dpt") {
        // EKLP
        // EKLP
        // 1️⃣  Start rangkaian EKLP (serial)
        const dptPromises = [];

        await Promise.all(
            children.map(async (child) => {
                await scrapeEklp({
                    data: { childId: child.id },
                    action,
                    type: "child",
                    mode,
                });
                dptPromises.push(scrapeDpt({
                    data: { childId: child.id, parentId: parent.id },
                    action,
                    type: "child",
                    mode,
                }));
            })
        );

        await Promise.all(dptPromises);

        // 4️⃣  Lanjut ke bawah...
        console.log("Semua EKLP & DPT rampung!");
      } else if (mode === "sipp_dpt") {
        // 只跑 DPT
        for (const child of children) {
          await scrapeDpt({
            data: { childId: child.id, parentId: parent.id },
            action: action,
            type: "child",
            mode: mode,
          });
        }
      }
    }
  } catch (error) {
    console.error("❌ generateAll error:", error);
    await db.query('UPDATE running_jobs SET status = "error" WHERE id = ?', [
      jobId,
    ]);
    return { status: "error", message: error.message };
  } finally {
    await db.query('UPDATE running_jobs SET status = "finish" WHERE id = ?', [
      jobId,
    ]);
  }
  return { status: "success" };
}
