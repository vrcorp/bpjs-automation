// telegram-notif.js
import fetch from 'node-fetch';      // npm i node-fetch@3
import dotenv from "dotenv";
dotenv.config();

/**
 * Kirim pesan Telegram ke chat id tertentu.
 * @param {string|number} chatId - user / grup / channel id  (ex: 987654321 atau -1001234567890)
 * @param {string} text          - isi pesan (boleh HTML/Markdown)
 * @returns {Promise<object>}    - result dari Bot API (message object)
 */
export async function sendTelegramNotif(chatId, text) {
  const TG_TOKEN = process.env.TG_TOKEN;
  if (!TG_TOKEN) throw new Error('Env TG_TOKEN belum di-set');

  const res = await fetch(
    `https://api.telegram.org/bot${TG_TOKEN}/sendMessage`,
    {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        chat_id: chatId,
        text,
        parse_mode: 'HTML', // ganti 'MarkdownV2' kalau suka markdown
      }),
    },
  );

  const data = await res.json();
  if (!data.ok) {
    throw new Error(`Telegram API ${data.error_code}: ${data.description}`);
  }
  return data.result; // berisi message_id, date, dst.
}
