// logger.js
import fs from "fs";
import path from "path";

// Buat file log jika belum ada
const logStream = fs.createWriteStream(path.join("./log.txt"), { flags: "a" });
const originalLog = console.log;

console.log = (...args) => {
  const timestamp = new Date().toISOString();
  const message = args.join(" ");
  logStream.write(`[${timestamp}] ${message}\n`);
  originalLog(...args);
};

const originalError = console.error;
console.error = (...args) => {
  const timestamp = new Date().toISOString();
  const message = args.join(" ");
  logStream.write(`[${timestamp}] ERROR: ${message}\n`);
  originalError(...args);
};