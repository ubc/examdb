# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.require_version ">= 1.5.1"

if ENV['VAGRANT_HOME'].nil?
      ENV['VAGRANT_HOME'] = './'
end

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.hostmanager.enabled = true
  config.hostmanager.manage_host = true
  config.hostmanager.include_offline = true

  config.vm.box = "puppetlabs/ubuntu-14.04-64-puppet"
  config.vm.box_version = "1.0.1"
  config.vm.network "private_network", ip: "192.168.50.4"
  config.vm.network "forwarded_port", guest: 80, host: 8089, auto_correct: true
  #config.vm.network "forwarded_port", guest: 3306, host: 23306, auto_correct: true
  config.vm.synced_folder "./", "/www_data/app", create: true, type: :nfs
  #config.bindfs.bind_folder "/vagrant-nfs", "/www_data/app"
  config.vm.hostname = "examdb.dev"
  config.vm.provision :shell, :inline => <<-SHELL
    apt-get update
    puppet module install compass-examdb
  SHELL

  config.vm.provision "puppet" do |puppet|
    puppet.manifests_path = "puppet"
    puppet.manifest_file  = "site.pp"
    puppet.hiera_config_path = "puppet/hiera.yaml"
    puppet.options = "--environment", "dev"
  end

  config.vm.provision :shell, :inline => <<-SHELL
    sudo su - vagrant
    cd /www_data/app
    php app/console doctrine:schema:update --force
    php app/console cache:clear --env=prod
    php app/console cache:warmup --env=prod
    php app/console assetic:dump --env=prod
    php app/console exam:subjectcode:refresh --local
    php app/console exam:user:create admin admin ROLE_SUPER_ADMIN
  SHELL
end
