import { exec } from "child_process";
import util from "util";

const sh = util.promisify(exec);
const MAX_BUF = 1024 * 1024;          // 1 MB log buffer

async function run(cmd) {
  try {
    const { stdout } = await sh(cmd, { maxBuffer: MAX_BUF, shell: true });
    return stdout.trim();
  } catch (e) {
    throw new Error(e.stderr || e.message);
  }
}

/** @param {string} name  – nama proses PM2
  * @param {string} args  – argumen yg dikirim ke worker.js
  * @param {string} profile – nama folder cache Chrome (boleh kosong)
  */
async function pm2Start({ name, args, profile = "", autorestart = true }) {
  // Skip jika proses sudah ada
  const desc = await run(`pm2 jlist | grep -w '"name":"${name}"' || true`);
  if (desc) return `[pm2] ${name} already running`;

  const envPart = profile
    ? `npx cross-env PUP_PROFILE="${profile}"`
    : "";

  const arFlag = autorestart ? "" : "--no-autorestart";
  const cmd    = `${envPart} pm2 start worker.js --name ${name} ${arFlag} -- ${args}`;

  return run(cmd);
}

/* ──────────── Export helper yang dipakai API ──────────── */

export const runGenerate = (mode = "default") =>
  pm2Start({
    name: `sipp-${mode}`,
    args: `--mode=${mode}`,
    profile: `chrome-${mode}`
  });

export const runParentById = (id) =>
  pm2Start({
    name: `parent-${id}`,
    args: `--parent=${id}`,
    profile: `chrome-parent-${id}`,
  });

export const runChildById = (id) =>
  pm2Start({
    name: `child-${id}`,
    args: `--child=${id}`,
    profile: `chrome-child-${id}`,
  });
