import dotenv from "dotenv";
dotenv.config(); // ⬅️ load .env

import mysql from "mysql2/promise";

export const db = await mysql.createConnection({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT, // optional
  user: process.env.DB_USER,
  password: process.env.DB_PASS,
  database: process.env.DB_NAME,
});

export default db;
