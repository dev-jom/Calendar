Pré-requisitos:

    Docker e Docker Compose instalados

    Laradock configurado e funcionando

    Sistema operacional Linux

Passo a passo completo:

1- Clonar o projeto:
Na sua pasta de projetos: cd ~/projects
git clone https://github.com/dev-jom/Calendar.git

2- Iniciar os containers do Laradock:
Dentro da pasta do Laradock: cd ~/projects/laradock
sudo docker compose up -d nginx postgres workspace

3- Configurar o ambiente do projeto:
Abra um novo terminal e execute: sudo docker compose exec workspace su laradock
Dentro do workspace, execute: cd /var/www/Calendar
cp .env.example .env

4- Configurar o banco de dados no arquivo .env:


5- Criar o banco de dados no PostgreSQL:
Em outro terminal (fora do container): sudo docker compose exec postgres psql -U default -c "CREATE DATABASE calendar;"

6- Instalar as dependências do composer:
No terminal do workspace (já dentro do container): composer install

7- Gerar a chave da aplicação:
No workspace: php artisan key:generate

8- Criar link simbólico do storage:
No workspace: php artisan storage:link
PS: cria um atalho público para a pasta storage, permitindo acessar arquivos via navegador

9- Executar as migrações do banco de dados:
No workspace: php artisan migrate

10- Configurar o arquivo hosts:
No terminal do seu computador (fora dos containers): sudo nano /etc/hosts
Adicione a linha: 127.0.1.1 calendar

11- Configurar o nginx:
Dentro da pasta do Laradock: cd nginx/sites/
Crie o arquivo de configuração: cp laravel.conf.example calendar.conf
Edite o arquivo calendar.conf e altere:
server_name calendar;
root /var/www/Calendar/public;

12- Reiniciar o nginx:
Dentro da pasta do Laradock: sudo docker compose restart nginx

13- Acessar a aplicação:
Abra o navegador e acesse: http://calendar/
Solução de problemas comuns:

Se não funcionar:
1- Derrube os containers: sudo docker compose down
2- Suba novamente: sudo docker compose up -d nginx postgres workspace

Se der erro de permissões:
sudo docker compose exec workspace bash -c "cd /var/www/Calendar && chown -R laradock:laradock . && chmod -R 755 storage && chmod -R 755 bootstrap/cache"

Se o banco não conectar:
Verifique se o PostgreSQL está rodando: sudo docker compose ps | grep postgres
URLs importantes:

    Aplicação principal: http://calendar/

    Preview do calendário: http://calendar/preview/calendar

    Preview de tarefas: http://calendar/preview/task-test/{data}

Comandos úteis para desenvolvimento:

Entrar no container: sudo docker compose exec workspace su laradock
Listar rotas: php artisan route:list
Limpar cache: php artisan optimize:clear
Ver logs: sudo docker compose logs nginx

Pronto! Seu calendário está funcionando localmente.
