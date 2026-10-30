<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

$tituloPagina = 'Locais de atendimento e convênios';

require dirname(__DIR__) . '/includes/header.php';
?>

<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

<style>
  #mapa {
    position: relative;
    z-index: 0;
    height: 450px;
    width: 100%;
    border-radius: 8px;
    margin: 18px 0;
  }

  .busca-locais {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 16px 0;
  }

  .busca-locais input {
    flex: 1 1 280px;
    min-width: 0;
  }

  .local-item {
    padding: 14px;
    margin-bottom: 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
  }

  .local-item p {
    margin: 4px 0;
  }

  .local-item button {
    margin-top: 8px;
  }
</style>

<section class="section">
  <div class="container container-narrow">
    <h1>Locais de atendimento e convênios</h1>

    <p class="subtitle">
      Encontre locais cadastrados, compare distâncias aproximadas
      e consulte os convênios aceitos.
    </p>

    <form id="form-endereco" class="busca-locais">
      <label class="sr-only" for="endereco-busca">
        Seu endereço
      </label>

      <input
        id="endereco-busca"
        type="search"
        placeholder="Digite seu endereço, cidade e estado"
        minlength="3"
        required
      >

      <button class="btn btn-primary" type="submit">
        Buscar endereço
      </button>

      <button
        class="btn btn-outline"
        type="button"
        id="usar-localizacao"
      >
        Usar minha localização
      </button>
    </form>

    <label for="filtro-tipo">
      <strong>Filtrar por tipo:</strong>
    </label>

    <select id="filtro-tipo">
      <option value="Todos">Todos</option>
      <option value="Clinica">Clínicas</option>
      <option value="Hospital">Hospitais</option>
      <option value="Laboratorio">Laboratórios</option>
    </select>

    <p id="estado-locais" role="status" aria-live="polite">
      Carregando locais...
    </p>

    <div
      id="mapa"
      role="region"
      aria-label="Mapa de locais de atendimento"
    ></div>

    <p>
      <small>
        Distância aproximada em linha reta.
        Os endereços de demonstração podem ser fictícios.
        Busca de endereço: OpenStreetMap/Nominatim.
      </small>
    </p>

    <h2>Clínicas, hospitais e laboratórios cadastrados</h2>

    <div id="lista-locais"></div>
  </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
(() => {
  'use strict';

  const centroPadrao = [-15.793889, -47.882778];

  const estado = document.getElementById('estado-locais');
  const lista = document.getElementById('lista-locais');
  const filtro = document.getElementById('filtro-tipo');
  const formulario = document.getElementById('form-endereco');
  const botaoLocalizacao = document.getElementById('usar-localizacao');

  const mapa = L.map('mapa', {
    zoomAnimation: false,
    fadeAnimation: false,
    markerZoomAnimation: false
  }).setView(centroPadrao, 12);

  const marcadores = L.layerGroup().addTo(mapa);

  let marcadorOrigem = null;
  let origem = centroPadrao;
  let locais = [];

  L.tileLayer(
    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
      attribution: '&copy; OpenStreetMap contributors'
    }
  ).addTo(mapa);

  function distanciaKm(lat1, lon1, lat2, lon2) {
    const rad = graus => graus * Math.PI / 180;

    const dLat = rad(lat2 - lat1);
    const dLon = rad(lon2 - lon1);

    const valor =
      Math.sin(dLat / 2) ** 2 +
      Math.cos(rad(lat1)) *
      Math.cos(rad(lat2)) *
      Math.sin(dLon / 2) ** 2;

    return 6371 * 2 * Math.asin(
      Math.min(1, Math.sqrt(valor))
    );
  }

  function posicaoValida(latitude, longitude) {
    return (
      Number.isFinite(latitude) &&
      Number.isFinite(longitude) &&
      latitude >= -90 &&
      latitude <= 90 &&
      longitude >= -180 &&
      longitude <= 180
    );
  }

  function linkGoogle(local) {
    const consulta = `${local.latitude},${local.longitude}`;

    return (
      'https://www.google.com/maps/search/?api=1&query=' +
      encodeURIComponent(consulta)
    );
  }

  function detalhes(local) {
    const conteudo = document.createElement('div');
    const titulo = document.createElement('strong');

    titulo.textContent = local.nome;
    conteudo.appendChild(titulo);

    const tipo = {
      Clinica: 'Clínica',
      Hospital: 'Hospital',
      Laboratorio: 'Laboratório'
    }[local.tipo] || local.tipo;

    const textos = [
      tipo,
      local.endereco,
      'Distância aproximada: ' +
        local.distancia.toFixed(1) + ' km',
      'Convênios: ' + local.convenios_aceites
    ];

    for (const texto of textos) {
      const linha = document.createElement('div');
      linha.textContent = texto;
      conteudo.appendChild(linha);
    }

    const link = document.createElement('a');

    link.href = linkGoogle(local);
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.textContent = 'Abrir no Google Maps';

    conteudo.appendChild(link);

    return conteudo;
  }

  function renderizar() {
    marcadores.clearLayers();
    lista.replaceChildren();

    const selecionados = locais
      .filter(local => {
        return (
          filtro.value === 'Todos' ||
          local.tipo === filtro.value
        );
      })
      .filter(local => {
        return posicaoValida(
          Number(local.latitude),
          Number(local.longitude)
        );
      })
      .map(local => ({
        ...local,
        distancia: distanciaKm(
          origem[0],
          origem[1],
          Number(local.latitude),
          Number(local.longitude)
        )
      }))
      .sort((a, b) => a.distancia - b.distancia);

    if (!selecionados.length) {
      estado.textContent =
        'Nenhum local cadastrado para este filtro.';
      return;
    }

    estado.textContent =
      `${selecionados.length} local(is) encontrado(s), ` +
      'ordenados por distância aproximada.';

    for (const local of selecionados) {
      const marcador = L.marker([
        Number(local.latitude),
        Number(local.longitude)
      ]).bindPopup(detalhes(local));

      marcadores.addLayer(marcador);

      const item = document.createElement('article');
      item.className = 'local-item';
      item.appendChild(detalhes(local));

      const botao = document.createElement('button');

      botao.type = 'button';
      botao.className = 'btn btn-outline btn-sm';
      botao.textContent = 'Ver no mapa';

      botao.addEventListener('click', () => {
        mapa.setView(
          marcador.getLatLng(),
          15,
          { animate: false }
        );

        marcador.openPopup();

        const modoTea =
          document.documentElement.classList.contains('modo-tea');

        document.getElementById('mapa').scrollIntoView({
          behavior: modoTea ? 'instant' : 'smooth'
        });
      });

      item.appendChild(botao);
      lista.appendChild(item);
    }
  }

  function definirOrigem(latitude, longitude, descricao) {
    const lat = Number(latitude);
    const lon = Number(longitude);

    if (!posicaoValida(lat, lon)) {
      throw new Error('Coordenadas inválidas.');
    }

    origem = [lat, lon];

    if (marcadorOrigem) {
      mapa.removeLayer(marcadorOrigem);
    }

    marcadorOrigem = L.circleMarker(origem, {
      radius: 9,
      color: '#006b68',
      fillColor: '#2ec4b6',
      fillOpacity: 1
    });

    const textoOrigem = document.createElement('span');
    textoOrigem.textContent = descricao;

    marcadorOrigem.addTo(mapa).bindPopup(textoOrigem);

    mapa.setView(origem, 12, { animate: false });

    renderizar();
  }

  filtro.addEventListener('change', renderizar);

  formulario.addEventListener('submit', async evento => {
    evento.preventDefault();

    const endereco =
      document.getElementById('endereco-busca').value.trim();

    if (endereco.length < 3) {
      return;
    }

    const botao =
      formulario.querySelector('button[type="submit"]');

    botao.disabled = true;
    estado.textContent = 'Buscando endereço...';

    try {
      const parametros = new URLSearchParams({
        q: endereco,
        format: 'jsonv2',
        limit: '1',
        countrycodes: 'br'
      });

      const resposta = await fetch(
        'https://nominatim.openstreetmap.org/search?' +
        parametros
      );

      if (!resposta.ok) {
        throw new Error(
          'Serviço de endereços indisponível.'
        );
      }

      const resultados = await resposta.json();

      if (!resultados.length) {
        throw new Error(
          'Endereço não encontrado. Informe rua, cidade e estado.'
        );
      }

      definirOrigem(
        resultados[0].lat,
        resultados[0].lon,
        'Endereço informado'
      );
    } catch (erro) {
      estado.textContent =
        erro.message || 'Não foi possível buscar o endereço.';
    } finally {
      botao.disabled = false;
    }
  });

  botaoLocalizacao.addEventListener('click', () => {
    if (!navigator.geolocation) {
      estado.textContent =
        'Localização indisponível neste navegador. Digite um endereço.';
      return;
    }

    estado.textContent = 'Obtendo sua localização...';

    navigator.geolocation.getCurrentPosition(
      posicao => {
        definirOrigem(
          posicao.coords.latitude,
          posicao.coords.longitude,
          'Sua localização'
        );
      },
      () => {
        estado.textContent =
          'Permissão negada ou localização indisponível. ' +
          'Digite um endereço.';
      },
      {
        timeout: 10000,
        enableHighAccuracy: false
      }
    );
  });

  definirOrigem(
    ...centroPadrao,
    'Centro de Brasília (posição inicial)'
  );

  fetch(<?= json_encode(url('api/locais.php')) ?>)
    .then(resposta => {
      if (!resposta.ok) {
        throw new Error('Falha ao consultar locais.');
      }

      return resposta.json();
    })
    .then(dados => {
      if (!Array.isArray(dados)) {
        throw new Error('Resposta inválida da API.');
      }

      locais = dados;
      renderizar();
    })
    .catch(() => {
      estado.textContent =
        'Não foi possível carregar os locais cadastrados.';
    });
})();
</script>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>