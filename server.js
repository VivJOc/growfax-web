import express from "express";
import dotenv from "dotenv";
import fetch from "node-fetch";

dotenv.config();

const app = express();
const PORT = process.env.PORT || 3000;
const webhook = process.env.DISCORD_WEBHOOK_URL || "";

app.use(express.static("public"));
app.use(express.json());

app.get("/", (req, res) => {
  res.sendFile("index.html", { root: "public" });
});

app.post("/webhook", async (req, res) => {
  if (!webhook) return res.status(400).send("Webhook URL not set");
  try {
    const body = req.body;
    await fetch(webhook, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        content: `🧾 **New Role Purchase**
**Role:** ${body.role}
**GrowID:** ${body.growid}
**Email:** ${body.email}
**Amount:** ${body.amount}`
      })
    });
    res.status(200).send("Sent to Discord");
  } catch (err) {
    console.error(err);
    res.status(500).send("Failed to send webhook");
  }
});

app.listen(PORT, () => console.log(`✅ Server running at http://localhost:${PORT}`));
