#!/usr/bin/env node
// Simple WebSocket-based signaling server for WebRTC offers/answers/ICE
// Usage: node signaling-server.js

const WebSocket = require('ws');

const PORT = process.env.PORT || 3000;
const wss = new WebSocket.Server({ port: PORT });

// Map of clientId -> ws
const clients = new Map();

function send(ws, obj) {
  try {
    ws.send(JSON.stringify(obj));
  } catch (err) {
    console.error('send error', err);
  }
}

console.log(`Signaling server starting on ws://localhost:${PORT}`);

wss.on('connection', function connection(ws) {
  let clientId = null;

  ws.on('message', function incoming(message) {
    // log raw incoming message for debugging
    try { console.log('[server] raw recv:', message.toString()); } catch(e){}
    let msg;
    try {
      msg = JSON.parse(message.toString());
    } catch (err) {
      console.warn('invalid json from client:', message.toString());
      return;
    }

    const { type, from, to, payload } = msg;

    if (type === 'register') {
      // client registers with an id so others can route to it
      clientId = String(from);
      clients.set(clientId, ws);
      console.log(`client registered: ${clientId}`);
        // send registration ack with current peers list
        const peers = Array.from(clients.keys()).filter(id => id !== clientId);
        send(ws, { type: 'registered', id: clientId, peers });

        // broadcast updated peers list to all clients
        const allPeers = Array.from(clients.keys());
        for (const [id, sock] of clients.entries()) {
          try { send(sock, { type: 'peers', peers: allPeers }); } catch (e) { /* ignore */ }
        }
      return;
    }

    // for other message types, route to `to` if connected
    if (!to) {
      console.warn('message without `to` field:', msg);
      return;
    }

    const target = clients.get(String(to));
    if (!target) {
      // target not connected; notify sender
      console.log(`[server] forward failed: target ${to} not connected (from ${from})`);
      send(ws, { type: 'error', message: `target ${to} not connected` });
      return;
    }

    // forward the message to the target
    try{ console.log(`[server] forwarding ${type} from ${from} to ${to}`); }catch(e){}
    send(target, { type, from, payload });
  });

  ws.on('close', function () {
    if (clientId) {
      clients.delete(clientId);
      console.log(`client disconnected: ${clientId}`);
      // broadcast updated peers list to remaining clients
      const allPeers = Array.from(clients.keys());
      for (const [id, sock] of clients.entries()) {
        try { send(sock, { type: 'peers', peers: allPeers }); } catch (e) { /* ignore */ }
      }
    }
  });
});

wss.on('listening', () => console.log('Signaling server ready'));
