// signaling-server.js
const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 3000 });
console.log('Signaling server listening on ws://localhost:3000');

const clients = new Map(); // userid -> ws

wss.on('connection', (ws) => {
  ws.on('message', (msg) => {
    let data;
    try { data = JSON.parse(msg); } catch (e) { return; }

    // Expected message shapes:
    // { type: 'register', userid: '12345' }
    // { type: 'signal', to: 'otherid', from: 'myid', payload: { ... } }
    if (data.type === 'register' && data.userid) {
      clients.set(data.userid, ws);
      ws.userid = data.userid;
      console.log('registered', data.userid);
      return;
    }

    if (data.type === 'signal' && data.to) {
      const dest = clients.get(data.to);
      if (dest && dest.readyState === WebSocket.OPEN) {
        dest.send(JSON.stringify({ type: 'signal', from: data.from, payload: data.payload }));
      }
      return;
    }

    // optional: cleanup on close handled below
  });

  ws.on('close', () => {
    if (ws.userid) {
      clients.delete(ws.userid);
      console.log('disconnected', ws.userid);
    }
  });
});