# 🚀 Sistema de Intranet - Comercial Souza

![Status: Em Desenvolvimento](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)
![PHP: 8.x](https://img.shields.io/badge/php-8.x-blue)
![Tecnologias: TailwindCSS, JS](https://img.shields.io/badge/tecnologias-TailwindCSS%20%26%20JS-green)
![Banco: MySQL](https://img.shields.io/badge/banco-MySQL-orange)

---

## 📝 Descrição

**Contexto:**

A Comercial Souza necessitava de um portal interno centralizado para otimizar a comunicação, organizar o compartilhamento de documentos e facilitar o acesso a ferramentas essenciais do dia a dia, visando aumentar a produtividade e o engajamento dos colaboradores.

**Ação:**

Foi desenvolvida uma Intranet moderna e responsiva, utilizando PHP, MySQL e TailwindCSS. O sistema oferece um dashboard dinâmico com comunicados e um carrossel de imagens, um repositório de arquivos (PDFs, planilhas), um sistema de sugestões e reclamações, e áreas de acesso restrito para administradores.

**Resultado:**

A Intranet se tornou o ponto central de acesso à informação para todos os colaboradores. A plataforma agiliza a busca por documentos, centraliza comunicados importantes e fornece um canal direto para feedback, fortalecendo a cultura organizacional e a eficiência operacional da empresa.

---

## 🔧 Funcionalidades Principais

- ✅ **Autenticação Segura:** Sistema de login e registro com perfis de usuário (colaborador, administrador).
- ✅ **Dashboard Dinâmico:** Exibe comunicados importantes, um carrossel de imagens e atalhos rápidos para as principais seções.
- ✅ **Gestão de Documentos:** Seções dedicadas para PDFs e outros documentos, com criação de procedimentos padronizados.
- ✅ **Normas e Procedimentos:** Área específica para consulta de documentos normativos, organizados por setor para fácil acesso.
- ✅ **Sistema de Feedback:** Formulário para envio de sugestões e reclamações, com painel de gerenciamento para administradores atualizarem o status.
- ✅ **Painel Administrativo:** Área restrita para cadastro de informações, gerenciamento de usuários, setores e sistemas.
- ✅ **Notificações em Tempo Real:** Sistema de notificações para manter os usuários informados sobre novos procedimentos e outras atualizações.
- ✅ **Design Responsivo:** Interface totalmente adaptada para desktops, tablets e celulares.

---

## 📁 Estrutura do Projeto

---

intranet/
├── img/                      # Imagens e recursos visuais (logo, background)
├── uploads/                  # Pasta para arquivos enviados pelos usuários
├── vendor/                   # Dependências do Composer (DomPDF, etc)
├── adicionar_funcionario_matriz.php # API para adicionar funcionários na Matriz de Comunicação
├── atualizar_matriz.php      # API para editar a Matriz de Comunicação
├── cadastrar_carrossel.php   # API para adicionar imagens ao carrossel
├── cadastrar_informacao.php  # API para salvar comunicados
├── conexao.php               # Configuração da conexão com o banco de dados
├── get_notificacoes.php      # API para buscar notificações
├── index.php                 # Interface principal da intranet (SPA)
├── login.php                 # Tela de autenticação
├── logout.php                # Script para encerrar a sessão
├── save_procedure.php        # API para salvar novos procedimentos em PDF
├── salvar_sugestao.php       # API para salvar novas sugestões
└── README.md                 # Esta documentação
---

## 🛠️ Como Executar (Ambiente Local)

1. Instale o **XAMPP** (ou um ambiente similar com PHP e MySQL).
2. Copie a pasta `intranet/` para o diretório `C:/xampp/htdocs/`.
3. Inicie os módulos **Apache** e **MySQL** no painel de controle do XAMPP.
4. Crie o banco de dados `intranet` no **phpMyAdmin** (`http://localhost/phpmyadmin`).
5. Importe o arquivo `.sql` com a estrutura das tabelas para o banco de dados criado.
6. Acesse a intranet no seu navegador:

---

## 🔐 Usuários e Permissões

- **Autenticação:** Os usuários são validados contra a tabela `users` no banco de dados `intranet`.
- **Segurança:** As senhas são armazenadas de forma segura usando a função `password_hash` do PHP.
- **Sessão:** Após o login, os dados do usuário (ID, nome, permissão) são armazenados na sessão PHP.
- **Níveis de Acesso:** O sistema conta com os níveis `user`, `admin` e `god`. Administradores têm acesso a painéis de gerenciamento.

---

## 📸 Capturas de Tela

*Para exibir as imagens, crie uma pasta `screenshots` no projeto e adicione os caminhos aqui. Sugestões de capturas:*

### 1. 🔐 Tela de Login

*Interface de entrada do sistema, com a identidade visual da empresa.*

![Tela de Login](screenshots/login.png)

### 2. 🖥️ Dashboard Principal

*Visão geral com comunicados, carrossel de imagens e acesso rápido às funcionalidades.*

![Dashboard](screenshots/dashboard.png)

### 3. 📂 Normas e Procedimentos

*Seção para visualização e download de documentos PDF.*

![Normas e Procedimentos](screenshots/documentos.png)

### 4. 🗣️ Sugestões e Reclamações

*Formulário para feedback dos colaboradores e tela de gerenciamento para administradores.*

![Sugestões](screenshots/sugestoes.png)

### 5. ⚙️ Painel Administrativo

*Área restrita para gerenciamento de usuários, setores e outras configurações do sistema.*

![Painel Administrativo](screenshots/admin.png)

---

## 👨‍💻 Autor

- **Saulo Sampaio**

- **Matheus Cabral**

*Sistema desenvolvido para centralizar a comunicação e os recursos da Comercial Souza.*

---

## 📄 Licença

Projeto de uso interno.

Livre para adaptar conforme a necessidade da empresa.
