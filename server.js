// server.js

const express = require('express');
const http = require('http');
const WebSocket = require('ws');

const app = express();
const PORT = process.env.PORT || 8080; // Heroku fornece a porta no process.env.PORT

// Criar servidor HTTP
const server = http.createServer(app);

// Criar servidor WebSocket
const wss = new WebSocket.Server({ server });

// Quando um cliente se conecta
wss.on('connection', (ws) => {
  console.log('Novo cliente conectado');

  // Quando receber mensagem
  ws.on('message', (message) => {
    console.log('Mensagem recebida:', message);

    // Broadcast para todos os clientes conectados
    wss.clients.forEach((client) => {
      if (client !== ws && client.readyState === WebSocket.OPEN) {
        client.send(message);
      }
    });
  });

  // Quando o cliente desconectar
  ws.on('close', () => {
    console.log('Cliente desconectado');
  });
});

// Rota simples para testar se o servidor HTTP está online
app.get('/', (req, res) => {
  res.send('Servidor WebSocket está rodando!');
});

// Iniciar o servidor
server.listen(PORT, () => {
  console.log(`Servidor rodando na porta ${PORT}`);
});
