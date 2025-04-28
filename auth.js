// auth.js

// Simula um login (você pode substituir por login real ou Firebase)
document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
  
    if (loginForm) {
      loginForm.addEventListener("submit", (e) => {
        e.preventDefault();
  
        const username = document.getElementById("username").value;
        const password = document.getElementById("password").value;
  
        if (username && password) {
          // Simula login bem-sucedido
          localStorage.setItem("usuarioLogado", username);
  
          // Redireciona para o dashboard
          window.location.href = "dashboard.html";
        } else {
          alert("Preencha todos os campos.");
        }
      });
    }
  
    // Protege a página do dashboard
    if (window.location.pathname.includes("dashboard.html")) {
      const usuarioLogado = localStorage.getItem("usuarioLogado");
      if (!usuarioLogado) {
        window.location.href = "index.html";
      }
    }
  });
  
  function logout() {
    localStorage.removeItem("usuarioLogado");
    window.location.href = "index.html";
  }
  