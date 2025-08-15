
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
    
*Estrutura do Banco de Dados:*
![Estrutura do Banco de Dados](readme/bancodados.png)

---

## 🔐 Usuários e Permissões

-   **Autenticação:** Validação de usuários via tabela `users` no banco de dados `intranet`.
-   **Segurança:** Senhas armazenadas de forma segura com `password_hash` do PHP.
-   **Sessão:** Dados do usuário (ID, nome, permissão) armazenados na sessão PHP após o login.
-   **Níveis de Acesso:** `user`, `admin` e `god`. Administradores possuem acesso a painéis de gerenciamento.

---

## 📸 Capturas de Tela

### 1. ✨ Tela de Boas-Vindas
*A primeira tela que o usuário vê, apresentando a identidade visual e o propósito da intranet.*
![Tela de Boas-Vindas](readme/boasvindas.png)

### 2. 🔐 Acesso ao Sistema (Login)
*Modal de login que aparece após clicar em "Acessar Intranet", com campos para usuário e senha.*
![Tela de Login](readme/login.png)

### 3. 🔑 Recuperação de Senha
*Tela para usuários que esqueceram a senha, permitindo a solicitação de um link de redefinição.*
![Recuperação de Senha](readme/recuperarsenha.png)

### 4. 🏠 Tela Inicial (Dashboard)
*Visão geral do sistema após o login, com comunicados, atalhos e navegação principal.*
![Tela Inicial](readme/INICIAL.png)

### 5. 📂 Normas e Procedimentos
*Seção para consulta e download de documentos importantes da empresa.*
![Normas e Procedimentos](readme/NORMASPROCEDIMENTOS.png)

### 6. 📝 Criação de Procedimentos
*Formulário avançado com editor de texto para a criação de novos documentos de procedimento em PDF.*
![Criar Procedimento](readme/CRIARPROCEDIMENTO.png)

### 7. ⚙️ Configurações e Permissões
*Painel administrativo para gerenciamento de usuários, permissões de acesso e outras configurações do sistema.*
![Configuração](readme/CONFIGURACAO.png)

### 8. 👤 Perfil do Usuário
*Página onde o usuário pode alterar sua foto e senha.*
![Perfil](readme/PERFIL.png)

---

## 👨‍💻 Autor

**Saulo Sampaio**  
**Matheus Cabral**

*Sistema desenvolvido para centralizar a comunicação e os recursos da Comercial Souza.*

---

## 📄 Licença

Projeto de uso interno.  
Livre para adaptar conforme a necessidade da empresa.
