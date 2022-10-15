#Docker-magento on Fedora 36

    []: # Title: Docker-magento on Fedora 36

    []: # Author: [Roger]

#Docker Configuration for Magento

#First Install Docker and Docker Compose on Linux

    Running Docker on Linux should be pretty straight-forward. 
    Note that you need to run some post install 
    https://docs.docker.com/engine/install/linux-postinstall/ 
    commands as well as installing Docker Compose https://docs.docker.com/compose/install/ 
    before continuing. These steps are taken care of automatically with Docker Desktop, but not on Linux.

#generate public key and private key on Marketplace

    https://marketplace.magento.com/customer/accessKeys/

#Create your project:

    mkdir -p ~/Sites/magento
    cd $_

#Download the Docker Compose template:

    curl -s https://raw.githubusercontent.com/markshust/docker-magento/master/lib/template | bash

#Linux

    Running Docker on Linux should be pretty straight-forward. Note that you need to run some post install commands as well as installing Docker Compose before continuing. These steps are taken care of automatically with Docker Desktop, but not on Linux.
    
    Copy 
    docker-compose.dev-linux.yml to docker-compose.dev.yml 
    before installing Magento to take advantage of this setup.

#Download the version of Magento you want to use with:

    bin/download 2.4.4

    or for Magento core development: docker-compose -f docker-compose.yml up -d bin/setup-composer-auth bin/cli git clone git@github.com:magento/magento2.git . bin/cli git checkout 2.4-develop bin/composer install Run the setup installer for Magento:

#Configure public key and private key for Magento

    composer config --global http-basic.repo.magento.com <public key> <private key>

#or

    config during composer install

#after composer install and download magento

    bin/setup magento.test

    bin/magento setup:upgrade  

    open https://magento.test

#Bad 404 Error#

    echo "127.0.0.1 ::1 magento.test" | sudo tee -a /etc/hosts

    bin/magento admin:user:create --admin-user="username" --admin-password="password" --admin-email="email" --admin-firstname="Firste Name" --admin-lastname="Last Name"

    bin/magento module:disable Magento_TwoFactorAuth
    bin/magento sampledata:deploy 
    bin/magento setup:upgrade

#Permissions

    sudo chown username -R src
    sudo chmod -R 777 var/ pub/ generated/ app/code/
    chown username -R code/
    sudo chmod ugo+rwx -R code
    sudo chmod ugo+rwx -R design
    sudo chmod ugo+rwx -R etc

#or

    sudo chmod ugo+rwx -R var/ pub/ generated/ app/code/

Ref:

    https://github.com/markshust/docker-magento#docker-hub 
