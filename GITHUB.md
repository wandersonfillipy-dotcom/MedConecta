# Publicar o MedConecta no GitHub

## Pré-requisitos

- Conta no [GitHub](https://github.com)
- [Git](https://git-scm.com/download/win) instalado no Windows

Verifique no terminal:

```powershell
git --version
```

---

## Passo 1 — Criar o repositório no GitHub

1. Acesse https://github.com/new  
2. **Repository name:** `MedConecta` (ou outro nome)  
3. Escolha **Public** ou **Private**  
4. **Não** marque “Add a README” (o projeto já tem um)  
5. Clique em **Create repository**  
6. Copie a URL do repositório, por exemplo:  
   `https://github.com/SEU-USUARIO/MedConecta.git`

---

## Passo 2 — Abrir o terminal na pasta do projeto

```powershell
cd "C:\Users\luiza\OneDrive\Área de Trabalho\MedConecta"
```

---

## Passo 3 — Inicializar o Git (primeira vez)

```powershell
git init
git add .
git status
```

Confirme que **não** aparecem `.env` nem senhas (o `.gitignore` já ignora `.env`).

---

## Passo 4 — Primeiro commit

```powershell
git commit -m "MedConecta: site com layout oficial, logo e CRUDs de exames, consultas e especialidades"
```

---

## Passo 5 — Conectar ao GitHub e enviar

Substitua `SEU-USUARIO` pelo seu usuário do GitHub:

```powershell
git branch -M main
git remote add origin https://github.com/SEU-USUARIO/MedConecta.git
git push -u origin main
```

O GitHub pode pedir login:

- **HTTPS:** use um [Personal Access Token](https://github.com/settings/tokens) como senha  
- **SSH:** configure chave SSH e use `git@github.com:SEU-USUARIO/MedConecta.git`

---

## Passo 6 — Conferir no site

Abra `https://github.com/SEU-USUARIO/MedConecta` e verifique se os arquivos aparecem.

---

## Atualizações depois do primeiro envio

Sempre que alterar o projeto:

```powershell
cd "C:\Users\luiza\OneDrive\Área de Trabalho\MedConecta"
git add .
git commit -m "Descrição do que mudou"
git push
```

---

## Dicas importantes

| Item | Ação |
|------|------|
| Arquivo `.env` | **Nunca** commitar — só `.env.example` |
| Logo e imagens | Já em `public/assets/img/` — vão para o GitHub |
| Banco MySQL | Não sobe no Git — só `database/schema.sql` |
| XAMPP | Cada pessoa importa o schema localmente |

---

## Opcional — GitHub Desktop

1. Instale [GitHub Desktop](https://desktop.github.com/)  
2. **File → Add local repository** → pasta `MedConecta`  
3. **Publish repository** para criar o remoto e enviar em um clique
