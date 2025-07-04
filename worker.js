// worker.js
import yargs from "yargs";
import { hideBin } from "yargs/helpers";
import { generateSipp } from "./function/generate_sipp.js";
import { resumeParent, resumeChild } from "./function/sipp_resume.js";
import { runBot } from "./bot/wa.js";

const argv = yargs(hideBin(process.argv))
  .option("mode",   { type: "string", describe: "global mode (default, eklp, lasik, ...)" })
  .option("parent", { type: "number", describe: "resume specific parentId" })
  .option("child",  { type: "number", describe: "resume specific childId" })
  .option("bot",    { type: "string", describe: "run bot tasks (wa, telegram, ...)" })
  .strict()
  .argv;

try {
  if (argv.mode) {
    console.log(`[worker] start generateSipp mode=${argv.mode}`);
    await generateSipp({ mode: argv.mode });
  } else if (argv.parent) {
    console.log(`[worker] resume parent id=${argv.parent}`);
    await resumeParent({ parentId: argv.parent });
  } else if (argv.child) {
    console.log(`[worker] resume child id=${argv.child}`);
    await resumeChild({ childId: argv.child });
  } else if (argv.bot === "wa") {
    console.log("[worker] run WhatsApp bot");
    await runBot();
  } else {
    console.error("Argumen tidak valid. Gunakan --help untuk melihat opsi.");
    process.exit(1);
  }

  // sukses ⇒ keluar normal (PM2 tidak restart karena autorestart=false untuk job sekali jalan)
  process.exit(0);
} catch (err) {
  console.error("[worker] Uncaught error:", err);
  process.exit(1);   // beri kode error supaya PM2 bisa restart kalau autorestart=true
}
