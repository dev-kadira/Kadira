![image](https://user-images.githubusercontent.com/89658833/189480151-42ee105c-143b-4697-9cdb-24d54a793279.png)

<div align="center">
  <h1>Kadira CRM / ERP</h1>
</div>
<div align="center">
  <strong>The perfect solution for small and medium-sized companies</strong>
</div>

<div align="center">
The Kadria project was born from a fork of VTiger 7.4. The aim of this project is to help companies that need a solution with well-defined business rules.
</div>

<br>

## Web Server installation

```bash
sudo apt update
sudo apt install nginx
```
We verify the services after installation

```bash
sudo systemctl stop nginx.service
sudo systemctl start nginx.service
sudo systemctl enable nginx.service
```

## MariaDB database installation

```bash
sudo apt-get install mariadb-server mariadb-client
```
We verify the services after installation

```bash
sudo systemctl stop mysql.service
sudo systemctl start mysql.service
sudo systemctl enable mysql.service
```
We activate the security of access to the database.

```bash
sudo mysql_secure_installation
```
We specify the following values in the different questions.

```bash
Enter current password for root (enter for none): Just press the Enter
Set root password? [Y/n]: Y
New password: Enter password
Re-enter new password: Repeat password
Remove anonymous users? [Y/n]: Y
Disallow root login remotely? [Y/n]: Y
Remove test database and access to it? [Y/n]: Y
Reload privilege tables now? [Y/n]: Y
```

Restart the service

```bash
sudo systemctl restart mysql.service
```

## Installation of PHP on NGINX web server

```bash
sudo apt-get install software-properties-common
sudo add-apt-repository ppa:ondrej/php

sudo apt update

sudo apt install php7.4-fpm php7.4-common php7.4-mbstring php7.4-xmlrpc php7.4-soap php7.4-gd php7.4-xml php7.4-intl php7.4-mysql php7.4-cli php7.4-mcrypt php7.4-ldap php7.4-zip php7.4-curl
```

Editing php.ini file to define the correct values to work with Kadira

```bash
sudo nano /etc/php/7.4/fpm/php.ini
```

- file_uploads = On
- allow_url_fopen = On
- memory_limit = 256M
- upload_max_filesize = 64M
- max_execution_time = 30
- display_errors = Off
- cgi.fix_pathinfo = 0
- max_input_vars = 1500

## Database and user creation

```sql
sudo mysql -u root -p

CREATE DATABASE kadira;
CREATE USER 'kadirauser'@'localhost' IDENTIFIED BY 'new_password_here';
GRANT ALL ON kadira.* TO 'kadirauser'@'localhost' IDENTIFIED BY 'user_password_here' WITH GRANT OPTION;

FLUSH PRIVILEGES;
EXIT;
````

We import the database of the base project

```sql
mysql -u username -p kadira < /var/www/html/kadira/schema/kadira.sql
````

## Download and install the project Kadira

```bash
cd /var/www/html/
git clone https://github.com/dev-kadira/Kadira.git
```
We apply permissions necessary for the web server to be able to write to the directory

```bash
sudo chown -R www-data:www-data /var/www/html/kadira/
sudo chmod -R 755 /var/www/html/kadira/
```

## Web server configuration

```bash
sudo nano /etc/nginx/sites-available/kadira
```

```
server {
    listen 80;
    listen [::]:80;
    root /var/www/html/kadira;
    index  index.php index.html index.htm;

     client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$args;        
    }

    location ~ \.php$ {
         include snippets/fastcgi-php.conf;
         fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
         fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
         include fastcgi_params;
    }
}
```

Once the changes have been applied, we activate the configuration on the nginx web server.

```bash
sudo ln -s /etc/nginx/sites-available/kadira/etc/nginx/sites-enabled/
```

To finish, we restart the web server

```bash
sudo systemctl restart nginx.service
```

With all changes completed, you should now be able to access the http:\localhost\kadira service.

The user data defined in the default database are as follows:

User: Admin

Password: Admin*123