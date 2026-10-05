/* MedConecta - máscaras e acessibilidade RF10/RF11 */

function mascaraCPF(input) {
  input.addEventListener('input', () => {
    let valor = input.value.replace(/\D/g, '').slice(0, 11);

    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
    valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

    input.value = valor;
  });
}

function mascaraTelefone(input) {
  input.addEventListener('input', () => {
    let valor = input.value.replace(/\D/g, '').slice(0, 11);

    valor = valor.length <= 10
      ? valor.replace(
          /(\d{2})(\d{4})(\d{0,4})/,
          '($1) $2-$3'
        )
      : valor.replace(
          /(\d{2})(\d{5})(\d{0,4})/,
          '($1) $2-$3'
        );

    input.value = valor;
  });
}

document.querySelectorAll('[data-mask="cpf"]').forEach(mascaraCPF);
document.querySelectorAll('[data-mask="telefone"]').forEach(mascaraTelefone);

(() => {
  'use strict';

  const raiz = document.documentElement;

  function salvarPreferencia(chave, valor) {
    try {
      localStorage.setItem(chave, valor);
    } catch (erro) {
      // Os controles continuam funcionando sem armazenamento local.
    }
  }

  /* RF10 - alto contraste */
  const btnContraste = document.getElementById('btn-contraste');

  if (btnContraste) {
    function atualizarContraste() {
      btnContraste.setAttribute(
        'aria-pressed',
        String(raiz.classList.contains('alto-contraste'))
      );
    }

    atualizarContraste();

    btnContraste.addEventListener('click', () => {
      const ativo = raiz.classList.toggle('alto-contraste');

      salvarPreferencia('mc_contraste', ativo ? '1' : '0');
      atualizarContraste();
    });
  }

  /* RF10 - três níveis de fonte */
  const botoesFonte = document.querySelectorAll('[data-fonte]');

  if (!['normal', 'grande', 'maior'].includes(raiz.dataset.fonte)) {
    raiz.dataset.fonte = 'normal';
  }

  function atualizarBotoesFonte() {
    botoesFonte.forEach(botao => {
      botao.setAttribute(
        'aria-pressed',
        String(raiz.dataset.fonte === botao.dataset.fonte)
      );
    });
  }

  atualizarBotoesFonte();

  botoesFonte.forEach(botao => {
    botao.addEventListener('click', () => {
      raiz.dataset.fonte = botao.dataset.fonte;

      salvarPreferencia(
        'mc_tamanho_fonte',
        botao.dataset.fonte
      );

      atualizarBotoesFonte();
    });
  });

  /* RF11 - Modo TEA */
  const btnTea = document.getElementById('btn-modo-tea');

  if (btnTea) {
    function atualizarModoTea() {
      btnTea.setAttribute(
        'aria-pressed',
        String(raiz.classList.contains('modo-tea'))
      );
    }

    atualizarModoTea();

    btnTea.addEventListener('click', () => {
      const ativo = raiz.classList.toggle('modo-tea');

      salvarPreferencia('mc_modo_tea', ativo ? '1' : '0');
      atualizarModoTea();
    });
  }

  /* Fechar configurações pelo teclado */
  const painel = document.getElementById('acessibilidade');

  if (painel) {
    painel.addEventListener('keydown', evento => {
      if (evento.key === 'Escape' && painel.open) {
        painel.open = false;
        painel.querySelector('summary').focus();
      }
    });
  }

  /* RF11 - temporizador visual de cinco minutos, sem som */
  const tempoTexto = document.getElementById('tempo-restante');

  if (tempoTexto) {
    const progresso = document.getElementById('tempo-progresso');
    const btnIniciar = document.getElementById('tempo-iniciar');
    const btnPausar = document.getElementById('tempo-pausar');
    const btnReiniciar = document.getElementById('tempo-reiniciar');

    const duracao = 300;

    let restante = duracao;
    let intervalo = null;

    function atualizarTempo() {
      const minutos = String(
        Math.floor(restante / 60)
      ).padStart(2, '0');

      const segundos = String(
        restante % 60
      ).padStart(2, '0');

      tempoTexto.value = `${minutos}:${segundos}`;
      progresso.value = duracao - restante;

      btnIniciar.disabled = intervalo !== null || restante === 0;
      btnPausar.disabled = intervalo === null;
    }

    function pausarTempo() {
      if (intervalo !== null) {
        clearInterval(intervalo);
        intervalo = null;
      }

      atualizarTempo();
    }

    btnIniciar.addEventListener('click', () => {
      if (intervalo !== null || restante === 0) {
        return;
      }

      intervalo = setInterval(() => {
        restante = Math.max(0, restante - 1);

        if (restante === 0) {
          pausarTempo();
        } else {
          atualizarTempo();
        }
      }, 1000);

      atualizarTempo();
    });

    btnPausar.addEventListener('click', pausarTempo);

    btnReiniciar.addEventListener('click', () => {
      pausarTempo();
      restante = duracao;
      atualizarTempo();
    });

    atualizarTempo();
  }

  /* Menu para telas pequenas */
  const menuToggle = document.getElementById('menu-toggle');
  const navPrincipal = document.getElementById('nav-principal');

  if (menuToggle && navPrincipal) {
    menuToggle.addEventListener('click', () => {
      const aberto = navPrincipal.classList.toggle('open');

      menuToggle.setAttribute(
        'aria-expanded',
        String(aberto)
      );

      menuToggle.setAttribute(
        'aria-label',
        aberto ? 'Fechar menu' : 'Abrir menu'
      );

      menuToggle.textContent = aberto ? '✕' : '☰';
    });
  }
})();