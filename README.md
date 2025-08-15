# 🚀 Sistema de Intranet - Comercial Souza

![Status: Em Desenvolvimento](https://img.shields.io/badge/status-em%20desenvolvimento-yellow)
![PHP: 8.x](https://img.shields.io/badge/php-8.x-blue)
![Tecnologias: TailwindCSS, JS](https://img.shields.io/badge/tecnologias-TailwindCSS%20%26%20JS-green)
![Banco: MySQL](https://img.shields.io/badge/banco-MySQL-orange)

---

## 📝 Descrição

**Contexto:**

Desenvolvimento de uma intranet para a Comercial Souza, visando centralizar a comunicação, otimizar o compartilhamento de documentos e facilitar o acesso a ferramentas essenciais para os colaboradores.

**Ação:**

Criação de uma intranet moderna e responsiva utilizando PHP, MySQL e TailwindCSS. O sistema integra um dashboard dinâmico, repositório de documentos, sistema de sugestões e áreas administrativas.

**Resultado:**

A intranet se estabeleceu como o principal hub de informações, agilizando o acesso a documentos, centralizando comunicados e oferecendo um canal direto para feedback, aprimorando a eficiência e a cultura organizacional.

---

## 🔧 Funcionalidades Principais

- ✅ **Autenticação Segura:** Login e registro com perfis de usuário (colaborador, administrador).
- ✅ **Dashboard Interativo:** Exibição de comunicados, carrossel de imagens e atalhos rápidos.
- ✅ **Gestão de Documentos (Normas e Procedimentos):** Seções dedicadas para PDFs e outros arquivos, incluindo criação de procedimentos padronizados.
- ✅ **Normas e Procedimentos:** Consulta organizada de documentos normativos por setor.
- ✅ **Sistema de Feedback:** Formulário para sugestões/reclamações com painel de gerenciamento para administradores.
- ✅ **Painel Administrativo:** Área restrita para cadastro de informações, gestão de usuários, setores e sistemas.
- ✅ **Notificações em Tempo Real:** Alertas sobre novos procedimentos e atualizações.
- ✅ **Design Responsivo:** Adaptação completa para desktops, tablets e smartphones.

---

## 📁 Estrutura do Projeto

```
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
```

---

## 🛠️ Como Executar (Ambiente Local)

1.  Instale o **XAMPP** (ou um ambiente similar com PHP e MySQL).
2.  Copie a pasta `intranet/` para o diretório `C:/xampp/htdocs/`.
3.  Inicie os módulos **Apache** e **MySQL** no painel de controle do XAMPP.
4.  Crie o banco de dados `intranet` no **phpMyAdmin** (`http://localhost/phpmyadmin`).
5.  Importe o arquivo `.sql` com a estrutura das tabelas para o banco de dados criado.
6.  Acesse a intranet no seu navegador:
    ```
    http://localhost/intranet/
    ```

---

## 🔐 Usuários e Permissões

-   **Autenticação:** Validação de usuários via tabela `users` no banco de dados `intranet`.
-   **Segurança:** Senhas armazenadas de forma segura com `password_hash` do PHP.
-   **Sessão:** Dados do usuário (ID, nome, permissão) armazenados na sessão PHP após o login.
-   **Níveis de Acesso:** `user`, `admin` e `god`. Administradores possuem acesso a painéis de gerenciamento.

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

**Saulo Sampaio**

**Matheus Cabral**

*Sistema desenvolvido para centralizar a comunicação e os recursos da Comercial Souza.*

---

## 📄 Licença

Projeto de uso interno.

Livre para adaptar conforme a necessidade da empresa.