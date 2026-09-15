# MedConecta 🏥

Plataforma de saúde digital acessível — prontuários, agendamentos e teleconsultas.  
Projeto acadêmico · Faculdade SENAC · Sprint 1

---

## Estrutura do projeto

```
MedConecta/
├── config/          ← Configurações (app e banco)
├── database/        ← Schema SQL
├── includes/        ← Bootstrap, funções, auth, header, footer
├── public/          ← Raiz pública (abrir no navegador)
│   ├── api/         ← Endpoints JSON (usados pelo JavaScript)
│   ├── assets/
│   │   ├── css/style.css
│   │   ├── js/
│   │   └── img/
│   ├── index.php
│   └── *.php        ← Páginas do site
├── .env.example     ← Modelo de configuração
├── .gitignore
└── README.md
```

---

## Instalação com XAMPP

1. Copie a pasta `MedConecta` para `C:\xampp\htdocs\`
2. Inicie **Apache** e **MySQL** no painel do XAMPP
3. Acesse `http://localhost/phpmyadmin` e importe `database/schema.sql`
4. Copie `.env.example` para `.env` (ajuste `DB_PASS` se necessário)
5. Abra `http://localhost/MedConecta/public/` no navegador

## Alternativa: servidor embutido do PHP

```bash
cd MedConecta
php -S localhost:8080 -t public
```
Acesse: `http://localhost:8080`

> **Atenção:** com o servidor embutido o `.env` funciona normalmente,  
> mas o `.htaccess` não tem efeito (só funciona com Apache).

---

## Fluxo básico

1. Acesse `/cadastro.php` e crie um paciente
2. Faça login em `/login.php`
3. Explore o dashboard, agendamento e documentos

---

## Stack

- **PHP 8+** · **MySQL** · **JavaScript (Vanilla)**
- Sessões PHP + CSRF token
- Bcrypt para senhas
- WCAG 2.1 (fonte 18pt+, botões 48px, ARIA)
- LGPD — consentimento obrigatório no cadastro
