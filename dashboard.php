<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'], $_SESSION['tipo_usuario'])) {
    header('Location: index.html');
    exit;
}

$uid   = $_SESSION['usuario_id'];
$tipo  = $_SESSION['tipo_usuario'];
$oposto = $tipo === 'aluno' ? 'professor' : 'aluno';

$stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE id <> :i AND tipo_usuario = :t");
$stmt->execute([':i' => $uid, ':t' => $oposto]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
  <style>
    *{box-sizing:border-box}body{margin:0;font-family:sans-serif;background:#f1f2f5}
    .toolbar{position:fixed;left:0;top:0;bottom:0;width:260px;background:#fff;border-right:1px solid #ddd;z-index:2000;display:flex;flex-direction:column;padding:16px;gap:16px}
    .toolbar button{padding:10px;border:1px solid #ccc;border-radius:6px;background:#fafafa;cursor:pointer;font-size:14px;text-align:left}
    .toolbar button:hover{background:#f0f0f0}.logout-btn{background:#f44336;color:#fff;border:none}
    main{margin-left:260px;height:100vh;display:flex}
    #calendar-wrapper{flex:1;padding:20px;overflow:auto}
    .chat-wrapper{flex:1;display:none;flex-direction:column;padding:20px}
    .messages{flex:1;overflow-y:auto;border:1px solid #ddd;padding:10px;border-radius:6px;background:#fff}
    .modal,#overlay{display:none}.modal{position:fixed;inset:0;z-index:2100;align-items:center;justify-content:center}
    .modal .box{background:#fff;padding:20px;border-radius:8px;width:90%;max-width:350px;max-height:80%;overflow:auto}
  </style>
</head>
<body>

<aside class="toolbar">
  <h2>Menu</h2>
  <button id="btnChat">💬 Iniciar Chat</button>
  <button id="btnCal">📅 Meu Calendário</button>

  <div id="user-panel" style="display:none">
    <h3>Conversando com<br><span id="destNome"></span></h3>
    <button class="logout-btn" onclick="fecharChat()">Fechar Conversa</button>
  </div>
</aside>

<main>
  <div id="calendar-wrapper"><div id="calendar"></div></div>

  <div id="chat-box" class="chat-wrapper">
    <button id="callBtn" onclick="iniciarCall()">📹 Videochamada</button>
    <div id="videoArea" style="display:none;gap:8px;margin-top:12px">
      <video id="localVideo" autoplay muted playsinline style="width:45%"></video>
      <video id="remoteVideo" autoplay playsinline style="width:45%"></video>
      <button onclick="hangup()">Encerrar</button>
    </div>

    <div id="incoming" style="display:none;position:fixed;inset:0;z-index:2200;background:rgba(0,0,0,.4);align-items:center;justify-content:center">
      <div style="background:#fff;padding:20px;border-radius:8px;max-width:300px;text-align:center">
        <p id="callerName" style="margin:0 0 16px"></p>
        <button id="btnAceitar">Atender</button>
        <button id="btnRecusar">Recusar</button>
      </div>
    </div>

    <audio id="ringtone" src="ring.mp3" loop></audio>

    <h3 id="chatTitle" style="margin:0 0 10px"></h3>
    <div id="mensagens" class="messages"></div>
    <div style="display:flex;gap:8px;margin-top:8px">
      <input id="mensagem" style="flex:1" placeholder="Digite...">
      <button onclick="enviarMensagem()">Enviar</button>
    </div>
  </div>
</main>

<div id="overlay"></div>

<div id="user-modal" class="modal">
  <div class="box">
    <h3>Escolha um usuário</h3>
    <ul id="lista-usuarios" style="list-style:none;padding:0"></ul>
    <button onclick="hide('user-modal')">Cancelar</button>
  </div>
</div>

<div id="modal-horas" class="modal">
  <div class="box">
    <h3>Escolha um horário</h3>
    <ul id="lista-horas" style="list-style:none;padding:0"></ul>
    <button onclick="closeHoras()">Voltar ao dia</button>
  </div>
</div>

<div id="modal-prof" class="modal">
  <div class="box">
    <h3>Professores disponíveis</h3>
    <ul id="lista-prof" style="list-style:none;padding:0"></ul>
    <button onclick="closeProf()">Voltar aos horários</button>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
/* -------- funções utilitárias -------- */
function show(id){document.getElementById(id).style.display='flex';overlay.style.display='block';}
function hide(id){document.getElementById(id).style.display='none';overlay.style.display='none';}

// Funções de chat
const USERS = <?= json_encode($usuarios, JSON_UNESCAPED_UNICODE) ?>;
document.getElementById('btnChat')?.addEventListener('click', () => { populateUsers(); show('user-modal'); });

function populateUsers() {
  const ul = document.getElementById('lista-usuarios');
  if (ul.childElementCount) return;
  USERS.forEach(u => {
    const li = document.createElement('li');
    li.textContent = u.nome;
    li.onclick = () => {
      sessionStorage.setItem('destId', u.id);
      sessionStorage.setItem('destNome', u.nome);
      history.pushState({ destId: u.id }, '', `?id_destinatario=${u.id}`);
      iniciarChat(); hide('user-modal');
    };
    ul.appendChild(li);
  });
}

let msgTimer = null;
function carregarMensagens() {
  const destId = sessionStorage.getItem('destId');
  if (!destId) return;

  fetch(`buscar_mensagens.php?id_destinatario=${destId}`)
    .then(response => response.json())
    .then(lista => {
      const box = document.getElementById('mensagens');
      box.innerHTML = ''; // Limpar mensagens anteriores

      if (lista.error) {
        console.error(lista.error);
        return;
      }

      lista.forEach(m => {
        const msgDiv = document.createElement('div');
        msgDiv.innerHTML = `<strong>${m.nome}:</strong> ${m.mensagem} <i>${m.data_envio}</i>`;
        box.appendChild(msgDiv);
      });

      box.scrollTop = box.scrollHeight;
    })
    .catch(error => {
      console.error('Erro ao carregar mensagens:', error);
    });
}


function iniciarChat() {
  chatBox.style.display = 'flex';
  calendarWrap.style.display = 'none';
  userPanel.style.display = 'block';
  destNome.textContent = sessionStorage.getItem('destNome');
  chatTitle.textContent = sessionStorage.getItem('destNome');
  clearInterval(msgTimer);
  carregarMensagens();
  msgTimer = setInterval(carregarMensagens, 4000);
}

function fecharChat() {
  clearInterval(msgTimer);
  chatBox.style.display = 'none';
  calendarWrap.style.display = 'block';
  userPanel.style.display = 'none';
  calendar.updateSize();
  history.pushState({}, '', window.location.pathname);
}

function enviarMensagem() {
  const msgInput = document.getElementById('mensagem');
  const msg = msgInput.value.trim();
  if (!msg) return;

  const destId = sessionStorage.getItem('destId');
  if (!destId) return alert('Nenhum usuário selecionado.');

  fetch('enviar_mensagem.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    mensagem: msg,
    id_destinatario: destId
  })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      msgInput.value = '';  // Limpar campo antes
      carregarMensagens(true);  // Só adiciona novas mensagens
    } else {
      alert('Erro ao enviar mensagem: ' + (data.error || 'Desconhecido'));
    }
  })
  .catch(error => {
    console.error('Erro ao enviar mensagem:', error);
    alert('Erro ao enviar mensagem.');
  });
}

let ultimaMensagemId = 0;

/* ---------- WebRTC ---------- */
/* ---------- WebRTC ---------- */
let pc, ws, localStream;
const cfg = { iceServers: [{urls: 'stun:stun.l.google.com:19302'}] };
const uid = <?= $uid ?>; // Certifique-se de que o PHP está passando o uid corretamente.
const localVideo = document.getElementById('localVideo');
const remoteVideo = document.getElementById('remoteVideo');
const videoArea = document.getElementById('videoArea');
const incoming = document.getElementById('incoming');
const ringtone = document.getElementById('ringtone');
const callerName = document.getElementById('callerName');
const btnAceitar = document.getElementById('btnAceitar');
const btnRecusar = document.getElementById('btnRecusar');

function wsSend(obj) {
  obj.from = uid;
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(JSON.stringify(obj));
  } else {
    console.error("WebSocket não está pronto");
  }
}

// Função para iniciar a chamada
function iniciarCall() {
  const dest = sessionStorage.getItem('destId');
  if (!dest) {
    alert('Nenhum usuário no chat.');
    return;
  }

  // Inicialize o WebSocket se não estiver inicializado
  if (!ws || ws.readyState !== WebSocket.OPEN) {
    ws = new WebSocket(`wss://${location.hostname}:8080?id_destinatario=${uid}`);
    ws.onmessage = (e) => signalling(JSON.parse(e.data));
    ws.onerror = () => console.error('Erro ao conectar com o WebSocket');
  }

  // Inicia o Peer (chamada)
  iniciarPeer(dest, true);
}

// Função para configurar a conexão Peer
async function iniciarPeer(dest, isCaller) {
  try {
    // Criação da RTCPeerConnection
    pc = new RTCPeerConnection(cfg);
    pc.onicecandidate = (e) => {
      if (e.candidate) wsSend({ to: dest, cand: e.candidate });
    };
    pc.ontrack = (e) => {
      remoteVideo.srcObject = e.streams[0]; // Exibe o vídeo remoto
    };

    // Solicita acesso à câmera e microfone
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    localStream.getTracks().forEach((t) => pc.addTrack(t, localStream)); // Adiciona as tracks do localStream no PeerConnection
    localVideo.srcObject = localStream;
    videoArea.style.display = 'flex'; // Exibe o localVideo

    if (isCaller) {
      // Criação da oferta de chamada
      const offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      wsSend({ to: dest, from: uid, sdp: pc.localDescription });
    }
  } catch (err) {
    console.error("Erro ao acessar mídia", err);
    alert('Erro ao acessar a câmera ou microfone. Verifique as permissões.');
  }
}

// Função de sinalização para gerenciar mensagens do WebSocket
async function signalling(msg) {
  if (msg.refuse) {
    alert('Chamada recusada');
    hangup(); // Encerra a chamada se recusada
    return;
  }

  if (msg.sdp) {
    if (msg.sdp.type === 'offer') {
      if (!pc) {
        // Quando chega uma oferta de chamada
        callerName.textContent = USERS.find(u => u.id === msg.from)?.nome + ' está ligando…';
        incoming.style.display = 'flex';
        ringtone.play();
        btnAceitar.onclick = () => {
          ringtone.pause();
          incoming.style.display = 'none';
          iniciarPeer(msg.from, false).then(async () => {
            await pc.setRemoteDescription(new RTCSessionDescription(msg.sdp));
            const ans = await pc.createAnswer();
            await pc.setLocalDescription(ans);
            wsSend({ to: msg.from, sdp: pc.localDescription });
          });
        };
        btnRecusar.onclick = () => {
          ringtone.pause();
          incoming.style.display = 'none';
          wsSend({ to: msg.from, refuse: true });
        };
      }
      return;
    }

    if (msg.sdp.type === 'answer' && pc) {
      await pc.setRemoteDescription(new RTCSessionDescription(msg.sdp));
      return;
    }
  }

  if (msg.cand && pc) {
    await pc.addIceCandidate(new RTCIceCandidate(msg.cand));
    return;
  }
}

// Função para encerrar a chamada
function hangup() {
  if (pc) {
    pc.close();
    pc = null;
  }
  videoArea.style.display = 'none';
  if (localStream) {
    localStream.getTracks().forEach((t) => t.stop());
  }
  if (ws) {
    ws.close();
    ws = null;
  }
}

/* ---------- Calendário ---------- */
const calendarWrap = document.getElementById('calendar-wrapper');
const chatBox = document.getElementById('chat-box');
const userPanel = document.getElementById('user-panel');
const destNome = document.getElementById('destNome');
const chatTitle = document.getElementById('chatTitle');

let calendar, selDia = '', selHora = '';

document.addEventListener('DOMContentLoaded', () => {
  calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
    initialView: 'dayGridMonth',
    height: 600,
    locale: 'pt-br',
    selectable: '<?= $tipo ?>' === 'aluno',
    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek'
    },
    events: 'calendar.php?feed=1',

    // Seleção de data
    select: handleDay,

    // Clique em evento (exclusão para professor)
    eventClick: (ev) => {
      if ('<?= $tipo ?>' === 'professor' && confirm('Excluir compromisso?')) {
        fetch('calendar.php', {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: ev.event.id })
        }).then(() => calendar.refetchEvents());
      }
    }
  });

  calendar.render();

  // Botão para mostrar calendário
  btnCal.onclick = () => {
    calendarWrap.style.display = 'block';
    chatBox.style.display = 'none';
    calendar.updateSize();
  };
});

// Função chamada ao selecionar uma data
function handleDay(info) {
  if ('<?= $tipo ?>' !== 'aluno') {
    calendar.unselect();
    return;
  }

  selDia = info.startStr.split('T')[0];

  fetch(`calendar.php?horas_livres_dia=1&dia=${selDia}`)
    .then(r => r.json())
    .then(horas => {
      if (!horas.length) {
        alert('Nenhum horário livre nesse dia.');
        calendar.unselect();
        return;
      }

      const ul = document.getElementById('lista-horas');
      ul.innerHTML = '';

      horas.forEach(h => {
        const li = document.createElement('li');
        li.textContent = h;
        li.style.cursor = 'pointer';
        li.onclick = () => {
          selHora = h;
          hide('modal-horas');
          loadProf();
        };
        ul.appendChild(li);
      });

      show('modal-horas');
    });
}

// Fecha modal de horários
function closeHoras() {
  hide('modal-horas');
  calendar.unselect();
}

// Carrega professores disponíveis no horário escolhido
function loadProf() {
  fetch(`calendar.php?prof_livres_slot=1&dia=${selDia}&hora=${selHora}`)
    .then(r => r.json())
    .then(lista => {
      const ul = document.getElementById('lista-prof');
      ul.innerHTML = '';

      lista.forEach(p => {
        const li = document.createElement('li');
        li.textContent = p.nome;
        li.style.cursor = 'pointer';
        li.onclick = () => {
          fetch('calendar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id_prof: p.id,
              dia: selDia,
              hora: selHora
            })
          }).then(() => {
            hide('modal-prof');
            calendar.refetchEvents();
            alert('Consulta agendada com sucesso!');
          });
        };
        ul.appendChild(li);
      });

      show('modal-prof');
    });
}

// Fecha modal de professores e volta para seleção de horários
function closeProf() {
  hide('modal-prof');
  show('modal-horas');
}

</script>
</body>
</html>
