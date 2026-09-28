/* MedConecta — Validação e máscaras */

function mascaraCPF(input) {
  input.addEventListener('input', () => {
    let v = input.value.replace(/\D/g,'').slice(0,11);
    v = v.replace(/(\d{3})(\d)/,'$1.$2');
    v = v.replace(/(\d{3})(\d)/,'$1.$2');
    v = v.replace(/(\d{3})(\d{1,2})$/,'$1-$2');
    input.value = v;
  });
}

function mascaraTelefone(input) {
  input.addEventListener('input', () => {
    let v = input.value.replace(/\D/g,'').slice(0,11);
    v = v.length <= 10
      ? v.replace(/(\d{2})(\d{4})(\d{0,4})/,'($1) $2-$3')
      : v.replace(/(\d{2})(\d{5})(\d{0,4})/,'($1) $2-$3');
    input.value = v;
  });
}

document.querySelectorAll('[data-mask="cpf"]').forEach(mascaraCPF);
document.querySelectorAll('[data-mask="telefone"]').forEach(mascaraTelefone);

/* Alto contraste */
const btnContraste = document.getElementById('btn-contraste');
if (btnContraste) {
  const KEY = 'mc_contraste';
  const aplicar = (on) => {
    document.body.classList.toggle('alto-contraste', on);
    btnContraste.setAttribute('aria-pressed', String(on));
  };
  aplicar(localStorage.getItem(KEY) === '1');
  btnContraste.addEventListener('click', () => {
    const novo = !document.body.classList.contains('alto-contraste');
    aplicar(novo);
    localStorage.setItem(KEY, novo ? '1' : '0');
  });
}

/* Menu mobile */
const menuToggle   = document.getElementById('menu-toggle');
const navPrincipal = document.getElementById('nav-principal');
if (menuToggle && navPrincipal) {
  menuToggle.addEventListener('click', () => {
    const aberto = navPrincipal.classList.toggle('open');
    menuToggle.setAttribute('aria-expanded', String(aberto));
    menuToggle.textContent = aberto ? '✕' : '☰';
  });
}