// status.js
import express from 'express';
import fs from 'fs';
const app = express();

app.get('/status', (req, res) => {
  try {
    // contoh: baca data pemain dari file / memory (simulasi dulu)
    const players = fs.existsSync('players.json') ? JSON.parse(fs.readFileSync('players.json')) : [];
    res.json({ online: players.length, timestamp: Date.now() });
  } catch (err) {
    res.json({ online: 0, error: err.message });
  }
});

app.listen(25945, '0.0.0.0', () => console.log('Status API running on port 25945'));
