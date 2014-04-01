# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

    ## Global Configs
    config.vm.box = "precise64"
    config.vm.box_url = "http://files.vagrantup.com/precise64.box"

    config.vm.network :private_network, ip: "10.33.33.10"
    config.ssh.forward_agent = true
    config.vm.host_name = "greenspace.dev"

    # Hostmanager plugin
    # config.hostmanager.enabled = true
    # config.hostmanager.manage_host = true
    # config.hostmanager.ignore_private_ip = false
    # config.hostmanager.include_offline = true

    config.vm.provider :virtualbox do |v|
        v.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
        v.customize ["modifyvm", :id, "--memory", 512]
        v.customize ["modifyvm", :id, "--name", "greenspace"]
    end

    nfs_setting = RUBY_PLATFORM =~ /darwin/ || RUBY_PLATFORM =~ /linux/
    config.vm.synced_folder "./frontend", "/server/greenspace/frontend", id: "vagrant-root" , :nfs => nfs_setting
    config.vm.provision :shell, :inline =>
        "if [[ ! -f /apt-get-run ]]; then sudo apt-get update && sudo touch /apt-get-run; fi"

    config.vm.provision :puppet do |puppet|
        puppet.manifests_path = "puppet/manifests"
        puppet.module_path = "puppet/modules"
        puppet.options = ['--verbose']
    end
end
