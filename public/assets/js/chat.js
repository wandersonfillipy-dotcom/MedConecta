(() => {
  'use strict';

  const config = window.MedConectaChat;

  if (!config) return;

  const elemento = {
    feedback: document.getElementById('chat-feedback'),
    titulo: document.getElementById('chat-titulo'),
    subtitulo: document.getElementById('chat-subtitulo'),
    status: document.getElementById('chat-status'),
    mensagens: document.getElementById('chat-mensagens'),
    formulario: document.getElementById('chat-formulario'),
    campo: document.getElementById('chat-campo'),
    enviar: document.getElementById('chat-enviar'),
    contagem: document.getElementById('chat-contagem'),
    encerrado: document.getElementById('chat-encerrado'),
    anexo: document.getElementById('chat-anexo'),
    anexoNome: document.getElementById('chat-anexo-nome'),
    anexoBotao: document.getElementById('chat-anexo-botao')
  };

  let conversaId = null;
  let carregando = false;
  let enviando = false;
  let ultimaMensagemId = 0;
  let conversaAtiva = false;

  const TAMANHO_MAXIMO = 10 * 1024 * 1024;

  const tiposPermitidos = {
    'application/pdf': ['pdf'],
    'image/jpeg': ['jpg', 'jpeg'],
    'image/png': ['png'],
    'image/webp': ['webp']
  };

  function mostrarFeedback(mensagem, tipo = 'erro') {
    elemento.feedback.textContent = mensagem;
    elemento.feedback.className = 'feedback';

    if (mensagem) {
      elemento.feedback.classList.add(
        tipo === 'sucesso' ? 'feedback-sucesso' : 'feedback-erro'
      );
    }
  }

  async function requisicao(url, opcoes = {}) {
    const headers = {
      Accept: 'application/json',
      ...(opcoes.headers || {})
    };

    // Não definir Content-Type manualmente para FormData.
    // O navegador precisa adicionar o boundary multipart.
    if (
      opcoes.body &&
      !(opcoes.body instanceof FormData)
    ) {
      headers['Content-Type'] = 'application/json';
    }

    const resposta = await fetch(url, {
      credentials: 'same-origin',
      ...opcoes,
      headers
    });

    let dados;

    try {
      dados = await resposta.json();
    } catch {
      throw new Error('O servidor retornou uma resposta inválida.');
    }

    if (!resposta.ok || !dados.ok) {
      throw new Error(
        dados.erro || 'Não foi possível concluir a operação.'
      );
    }

    return dados;
  }

  async function enviarAcao(dados) {
    return requisicao(config.apiUrl, {
      method: 'POST',
      body: JSON.stringify({
        ...dados,
        csrf: config.csrfToken
      })
    });
  }

  function formatarData(data) {
    if (!data) return '';

    const correspondencia = String(data).match(
      /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/
    );

    if (correspondencia) {
      const [, ano, mes, dia, hora, minuto] = correspondencia;
      return `${dia}/${mes}/${ano} às ${hora}:${minuto}`;
    }

    const dataConvertida = new Date(data);

    if (Number.isNaN(dataConvertida.getTime())) {
      return String(data);
    }

    return new Intl.DateTimeFormat('pt-BR', {
      dateStyle: 'short',
      timeStyle: 'short'
    }).format(dataConvertida);
  }

  function formatarTamanho(bytes) {
    if (!Number.isFinite(Number(bytes))) return '';

    const tamanho = Number(bytes);

    if (tamanho < 1024) return `${tamanho} B`;
    if (tamanho < 1024 * 1024) {
      return `${(tamanho / 1024).toFixed(1)} KB`;
    }

    return `${(tamanho / (1024 * 1024)).toFixed(2)} MB`;
  }

  function rolarParaFinal() {
    elemento.mensagens.scrollTop = elemento.mensagens.scrollHeight;
  }

  function mostrarCarregamento(texto = 'Carregando mensagens...') {
    elemento.mensagens.replaceChildren();

    const aviso = document.createElement('div');
    aviso.className = 'chat-carregando';
    aviso.textContent = texto;

    elemento.mensagens.appendChild(aviso);
  }

  function exibirMensagem(mensagem) {
    const minhaMensagem =
      Number(mensagem.remetente_id) === Number(config.usuarioId);

    const item = document.createElement('article');
    item.className = minhaMensagem
      ? 'chat-mensagem chat-mensagem-minha'
      : 'chat-mensagem chat-mensagem-outra';

    const balao = document.createElement('div');
    balao.className = 'chat-balao';

    const remetente = document.createElement('strong');
    remetente.className = 'chat-remetente';
    remetente.textContent = minhaMensagem
      ? 'Você'
      : (mensagem.remetente_nome || 'Participante');

    balao.appendChild(remetente);

    const textoMensagem = String(mensagem.mensagem || '');
    const nomeAnexo = String(mensagem.anexo_nome || '');

    // Mensagens automáticas "Anexo: nome.ext" não precisam
    // aparecer duplicadas quando já existe um link para o arquivo.
    const mensagemAutomatica = nomeAnexo &&
      textoMensagem === `Anexo: ${nomeAnexo}`;

    if (textoMensagem && !mensagemAutomatica) {
      const texto = document.createElement('p');
      texto.className = 'chat-texto';
      texto.textContent = textoMensagem;
      balao.appendChild(texto);
    }

    if (nomeAnexo) {
      const blocoAnexo = document.createElement('div');
      blocoAnexo.className = 'chat-anexo-mensagem';

      const icone = document.createElement('span');
      icone.setAttribute('aria-hidden', 'true');
      icone.textContent = '📎';

      const link = document.createElement('a');
      link.textContent = nomeAnexo;
      link.href = `${config.apiUrl}?baixar_anexo=${encodeURIComponent(mensagem.id)}`;
      link.setAttribute('download', nomeAnexo);
      link.rel = 'noopener';

      blocoAnexo.append(icone, link);

      if (mensagem.anexo_tamanho != null) {
        const tamanho = document.createElement('small');
        tamanho.textContent = formatarTamanho(mensagem.anexo_tamanho);
        blocoAnexo.appendChild(tamanho);
      }

      balao.appendChild(blocoAnexo);
    }

    const horario = document.createElement('time');
    horario.className = 'chat-horario';
    horario.textContent = formatarData(mensagem.enviada_em);

    if (mensagem.enviada_em) {
      horario.dateTime = String(mensagem.enviada_em).replace(' ', 'T');
    }

    balao.appendChild(horario);
    item.appendChild(balao);
    elemento.mensagens.appendChild(item);
  }

  function renderizarMensagens(mensagens) {
    elemento.mensagens.replaceChildren();
    ultimaMensagemId = 0;

    if (!Array.isArray(mensagens) || mensagens.length === 0) {
      const vazio = document.createElement('div');
      vazio.className = 'chat-vazio';
      vazio.textContent =
        'Esta conversa ainda não tem mensagens. Envie a primeira mensagem.';
      elemento.mensagens.appendChild(vazio);
      return;
    }

    mensagens.forEach(mensagem => {
      exibirMensagem(mensagem);

      ultimaMensagemId = Math.max(
        ultimaMensagemId,
        Number(mensagem.id) || 0
      );
    });

    rolarParaFinal();
  }

  function atualizarEstadoConversa(conversa) {
    const encerrada = conversa.status !== 'ativa';
    conversaAtiva = !encerrada;

    elemento.status.textContent = encerrada ? 'Encerrada' : 'Ativa';
    elemento.status.classList.toggle(
      'chat-status-encerrado',
      encerrada
    );

    elemento.formulario.hidden = encerrada;
    elemento.encerrado.hidden = !encerrada;

    if (config.perfil === 'paciente') {
      elemento.titulo.textContent =
        conversa.medico || 'Profissional responsável';

      elemento.subtitulo.textContent =
        conversa.especialidade || 'Consulta';
    } else {
      elemento.titulo.textContent =
        conversa.paciente_nome || 'Paciente';

      elemento.subtitulo.textContent =
        `${conversa.medico || 'Consulta'}${
          conversa.especialidade
            ? ' · ' + conversa.especialidade
            : ''
        }`;
    }
  }

  async function carregarHistorico(mostrarIndicador = false) {
    if (!conversaId || carregando) return;

    carregando = true;

    try {
      const url = new URL(config.apiUrl, window.location.href);
      url.searchParams.set('conversa_id', String(conversaId));

      const dados = await requisicao(url.toString());

      atualizarEstadoConversa(dados.conversa);

      const mensagens = Array.isArray(dados.mensagens)
        ? dados.mensagens
        : [];

      const maiorId = mensagens.reduce(
        (maior, mensagem) =>
          Math.max(maior, Number(mensagem.id) || 0),
        0
      );

      if (mostrarIndicador || ultimaMensagemId === 0) {
        renderizarMensagens(mensagens);
      } else if (maiorId > ultimaMensagemId) {
        // Redesenha o histórico quando chegam mensagens novas,
        // inclusive anexos enviados pelo outro participante.
        renderizarMensagens(mensagens);
      }
    } finally {
      carregando = false;
    }
  }

  async function iniciarChat() {
    const consultaId = Number(config.consultaId);

    if (!Number.isInteger(consultaId) || consultaId <= 0) {
      throw new Error(
        'Consulta não informada. Volte à área anterior e selecione uma consulta válida.'
      );
    }

    mostrarCarregamento('Preparando conversa...');

    const dados = await enviarAcao({
      acao: 'iniciar',
      consulta_id: consultaId
    });

    conversaId = Number(dados.conversa_id);

    if (!Number.isInteger(conversaId) || conversaId <= 0) {
      throw new Error('O servidor não retornou uma conversa válida.');
    }

    await carregarHistorico(true);
  }

  function validarArquivo(arquivo) {
    if (!arquivo) return 'Selecione um arquivo.';

    if (arquivo.size <= 0) {
      return 'O arquivo está vazio.';
    }

    if (arquivo.size > TAMANHO_MAXIMO) {
      return 'O arquivo excede o limite de 10 MB.';
    }

    const extensao = arquivo.name.includes('.')
      ? arquivo.name.split('.').pop().toLowerCase()
      : '';

    const extensoesValidas = Object.values(tiposPermitidos).flat();

    if (!extensoesValidas.includes(extensao)) {
      return 'Formato não permitido. Use PDF, JPG, PNG ou WEBP.';
    }

    if (
      arquivo.type &&
      !Object.prototype.hasOwnProperty.call(
        tiposPermitidos,
        arquivo.type
      )
    ) {
      return 'O tipo do arquivo não é permitido.';
    }

    if (
      arquivo.type &&
      !tiposPermitidos[arquivo.type].includes(extensao)
    ) {
      return 'A extensão do arquivo não corresponde ao tipo informado.';
    }

    return '';
  }

  function atualizarArquivoSelecionado() {
    const arquivo = elemento.anexo.files[0];

    if (!arquivo) {
      elemento.anexoNome.textContent = 'Nenhum arquivo selecionado';
      mostrarFeedback('');
      return;
    }

    const erro = validarArquivo(arquivo);

    if (erro) {
      elemento.anexo.value = '';
      elemento.anexoNome.textContent = 'Nenhum arquivo selecionado';
      mostrarFeedback(erro);
      return;
    }

    elemento.anexoNome.textContent =
      `${arquivo.name} (${formatarTamanho(arquivo.size)})`;

    mostrarFeedback('');
  }

  async function enviarMensagem(evento) {
    evento.preventDefault();

    if (enviando || !conversaAtiva || !conversaId) return;

    const mensagem = elemento.campo.value.trim();
    const arquivo = elemento.anexo.files[0];

    if (!mensagem && !arquivo) {
      mostrarFeedback('Digite uma mensagem ou selecione um arquivo.');
      elemento.campo.focus();
      return;
    }

    if (mensagem.length > 2000) {
      mostrarFeedback('A mensagem deve ter no máximo 2.000 caracteres.');
      return;
    }

    if (arquivo) {
      const erroArquivo = validarArquivo(arquivo);

      if (erroArquivo) {
        mostrarFeedback(erroArquivo);
        return;
      }
    }

    enviando = true;
    elemento.enviar.disabled = true;
    elemento.anexo.disabled = true;
    elemento.campo.disabled = true;
    mostrarFeedback('');

    try {
      if (arquivo) {
        const formulario = new FormData();

        formulario.append('acao', 'enviar_anexo');
        formulario.append('csrf', config.csrfToken);
        formulario.append('conversa_id', String(conversaId));
        formulario.append('mensagem', mensagem);
        formulario.append('anexo', arquivo);

        await requisicao(config.apiUrl, {
          method: 'POST',
          body: formulario
        });
      } else {
        await enviarAcao({
          acao: 'enviar',
          conversa_id: conversaId,
          mensagem
        });
      }

      elemento.campo.value = '';
      elemento.anexo.value = '';
      elemento.anexoNome.textContent = 'Nenhum arquivo selecionado';
      atualizarContagem();

      await carregarHistorico(true);

      mostrarFeedback(
        arquivo ? 'Anexo enviado com sucesso.' : 'Mensagem enviada com sucesso.',
        'sucesso'
      );

      elemento.campo.focus();
    } catch (erro) {
      mostrarFeedback(erro.message);
    } finally {
      enviando = false;
      elemento.enviar.disabled = false;
      elemento.anexo.disabled = false;
      elemento.campo.disabled = false;
    }
  }

  function atualizarContagem() {
    elemento.contagem.textContent = String(elemento.campo.value.length);
  }

  async function inicializar() {
    mostrarCarregamento();

    try {
      await iniciarChat();
    } catch (erro) {
      const mensagem = document.createElement('p');
      mensagem.className = 'chat-erro';
      mensagem.textContent = erro.message;

      elemento.mensagens.replaceChildren(mensagem);
      elemento.status.textContent = 'Indisponível';
      elemento.titulo.textContent = 'Chat indisponível';
      elemento.subtitulo.textContent = 'Verifique a consulta selecionada.';

      mostrarFeedback(erro.message);
    }
  }

  elemento.formulario.addEventListener('submit', enviarMensagem);
  elemento.campo.addEventListener('input', atualizarContagem);
  elemento.anexo.addEventListener('change', atualizarArquivoSelecionado);

  elemento.anexoBotao.addEventListener('keydown', evento => {
    if (evento.key === 'Enter' || evento.key === ' ') {
      evento.preventDefault();
      elemento.anexo.click();
    }
  });

  inicializar();

  window.setInterval(() => {
    if (!document.hidden && conversaId && !carregando) {
      carregarHistorico().catch(() => {
        // Uma falha temporária não apaga o histórico exibido.
      });
    }
  }, 5000);
})();
