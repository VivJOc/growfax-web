import express from "express";
import dotenv from "dotenv";
import fetch from "node-fetch";
import cors from "cors";

dotenv.config();
const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static("public"));

app.post("/send-webhook", async (req, res) => {
  try {
    const { roleName, growid, buyerEmail, paypal_name, paypal_email, amount } = req.body;
    const webhookUrl = process.env.DISCORD_WEBHOOK_URL;

    const discordData = {
      username: "Growfax Payment Bot",
      embeds: [{
        title: "💸 New Role Purchase",
        color: 5763719,
        fields: [
          { name: "Role", value: roleName, inline: true },
          { name: "GrowID", value: growid, inline: true },
          { name: "Buyer Email", value: buyerEmail, inline: true },
          { name: "PayPal Name", value: paypal_name, inline: true },
          { name: "PayPal Email", value: paypal_email, inline: true },
          { name: "Amount", value: `$${amount}`, inline: true }
        ],
        footer: { text: "Growfax PayPal System" },
        timestamp: new Date().toISOString()
      }]
    };

    const response = await fetch(webhookUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(discordData)
    });

    if (response.ok) res.sendStatus(200);
    else res.status(500).send("Failed to send webhook");
  } catch (error) {
    console.error(error);
    res.status(500).send("Error sending webhook");
  }
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`Server running on port ${port}`));
