<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
$tituloPagina = 'Busca de Locais e Convénios';

require dirname(__DIR__) . '/includes/header.php';
?>

<!-- Importação do CSS do Leaflet para o Mapa -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #mapa { height: 500px; width: 100%; margin-top: 20px; border-radius: 8px; }
    .filtros-mapa { margin-top: 15px; }
</style>

<section class="section">
  <div class="container container-narrow">
    <h1>Locais de Atendimento e Convênios</h1>
    <p class="subtitle">Encontre clínicas, hospitais e laboratórios próximos e verifique os convênios aceites.</p>
    
    <div class="filtros-mapa">
        <label for="filtro-tipo"><strong>Filtrar por tipo:</strong></label>
        <select id="filtro-tipo" onchange="carregarLocais()" style="padding: 5px; border-radius: 4px;">
            <option value="Todos">Todos</option>
            <option value="Clinica">Clínicas</option>
            <option value="Hospital">Hospitais</option>
            <option value="Laboratorio">Laboratórios</option>
        </select>
    </div>

    <!-- Contentor onde o mapa será renderizado -->
    <div id="mapa"></div>
  </div>
</section>

<!-- Importação do JavaScript do Leaflet e lógica do mapa -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    let mapa;
    let marcadores = [];

    function iniciarMapa(lat, lng) {
        mapa = L.map('mapa').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(mapa);
        
        L.marker([lat, lng]).addTo(mapa).bindPopup('<b>A sua localização</b>').openPopup();
        carregarLocais();
    }

   async function carregarLocais() {
        const filtro = document.getElementById('filtro-tipo').value;
        const containerLista = document.getElementById('lista-locais');
        try {
            const resposta = await fetch('api/locais.php');
            const locais = await resposta.json();

            // Limpa marcadores antigos do mapa
            marcadores.forEach(m => mapa.removeLayer(m));
            marcadores = [];
            
            let htmlLista = '<ul style="list-style: none; padding: 0;">';

            locais.forEach(local => {
                if (filtro === 'Todos' || local.tipo === filtro) {
                    // Adiciona ao mapa
                    const marcador = L.marker([local.latitude, local.longitude]).addTo(mapa);
                    const info = `
                        <b>${local.nome}</b><br>
                        Tipo: ${local.tipo}<br>
                        Endereço: ${local.endereco}<br>
                        Convênios: <i>${local.convenios_aceites}</i>
                    `;
                    marcador.bindPopup(info);
                    marcadores.push(marcador);

                    // Adiciona à lista textual
                    htmlLista += `
                        <li style="background: #f9f9f9; padding: 12px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ddd;">
                            <strong>${local.nome}</strong> (${local.tipo})<br>
                            <small>📍 ${local.endereco}</small><br>
                            <small style="color: #007bff;">🤝 Convênios: ${local.convenios_aceites}</small>
                        </li>
                    `;
                }
            });

            htmlLista += '</ul>';
            containerLista.innerHTML = htmlLista;

        } catch (erro) {
            console.error('Erro ao carregar locais:', erro);
            containerLista.innerHTML = '<p style="color: red;">Erro ao carregar os dados dos locais.</p>';
        }
    }

    // Obtém a geolocalização do utilizador ou usa coordenadas padrão (ex: Brasília)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => iniciarMapa(pos.coords.latitude, pos.coords.longitude),
            () => iniciarMapa(-15.793889, -47.882778)
        );
    } else {
        iniciarMapa(-15.793889, -47.882778);
    }
</script>

<section class="section" style="margin-top: 30px;">
  <div class="container container-narrow">
    <h3>Clínicas e Hospitais Cadastrados</h3>
    <div id="lista-locais" style="margin-top: 15px;">
        <p>A carregar lista de locais...</p>
    </div>
  </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>