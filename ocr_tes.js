import { Client } from "@gradio/client";
import fs from "fs";

const buffer = fs.readFileSync("./captcha.jpeg"); // <- Ini Buffer

const client = await Client.connect("Nischay103/captcha_recognition");

const result = await client.predict("/predict", {
  input: buffer, // Buffer langsung
});

console.log("Hasil OCR Captcha:", result.data);
