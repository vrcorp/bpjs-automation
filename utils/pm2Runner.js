import { exec } from "child_process";
import util from "util";

const sh = util.promisify(exec);            // promisify biar bisa await

function runCmd(cmd) {
  return sh(cmd, { maxBuffer: 1024 * 1024 }) // 1 MB buffer log
    .then(({ stdout }) => stdout.trim())
    .catch((e) => {
      console.error("[pm2Runner]", e.stderr || e);
      throw e;
    });
}

/* ───── helper umum ───── */
function pm2Start({ name, args, profile, autorestart = true }) {
  // jika proses dg nama itu sudah ada, skip (biar ga dobel)
  return runCmd(`pm2 describe ${name} || true`).then((out) => {
    if (out.includes(name)) return `[pm2] ${name} already running`;
    const env = profile ? `PUP_PROFILE=${profile} ` : "";
    const ar  = autorestart ? "" : "--autorestart=false";
    const cmd = `${env}pm2 start worker.js --name ${name} ${ar} -- ${args}`;
    return runCmd(cmd);
  });
}

/* ───────── exports ───────── */

export async function runGenerate(mode = "default") {
  const name    = `sipp-${mode}`;
  const profile = `chrome-${mode}`;
  return pm2Start({ name, args: `--mode=${mode}`, profile });
}

export async function runParentById(id) {
  const name    = `parent-${id}`;
  const profile = `chrome-parent-${id}`;
  return pm2Start({ name, args: `--parent=${id}`, profile, autorestart: false });
}

export async function runChildById(id) {
  const name    = `child-${id}`;
  const profile = `chrome-child-${id}`;
  return pm2Start({ name, args: `--child=${id}`, profile, autorestart: false });
}
