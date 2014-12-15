group { 'puppet': ensure => present }
Exec { path => [ '/bin/', '/sbin/', '/usr/bin/', '/usr/sbin/' ] }
File { owner => 0, group => 0, mode => 0644 }

class {'apt':
  always_apt_update => true,
}

Class['::apt::update'] -> Package <|
    title != 'python-software-properties'
and title != 'software-properties-common'
|>

apt::source { 'packages.dotdeb.org':
  location          => 'http://packages.dotdeb.org',
  release           => $lsbdistcodename,
  repos             => 'all',
  required_packages => 'debian-keyring debian-archive-keyring',
  key               => '89DF5277',
  key_server        => 'keys.gnupg.net',
  include_src       => true
}

if $lsbdistcodename == 'wheezy' {
  apt::source { 'packages.dotdeb.org-php55':
    location          => 'http://packages.dotdeb.org',
    release           => 'wheezy-php55',
    repos             => 'all',
    required_packages => 'debian-keyring debian-archive-keyring',
    key               => '89DF5277',
    key_server        => 'keys.gnupg.net',
    include_src       => true
  }
}


file { '/home/vagrant/.bash_aliases':
  ensure => 'present',
  source => 'puppet:///modules/puphpet/dot/.bash_aliases',
}
file { '/home/vagrant/.vimrc':
  ensure => 'present',
  source => 'puppet:///modules/puphpet/dot/.vimrc',
}
file { '/root/.vimrc':
  ensure => 'present',
  source => 'puppet:///modules/puphpet/dot/.vimrc',
}

package { [
    'build-essential',
    'vim',
    'curl',
    'git-core'
  ]:
  ensure  => 'installed',
}

class { 'nginx': }


#
# App vhost
#
$vhost = 'greenspace.dev.pedrocandeias.com'
$www_root = '/project/greenspace2/code/web'
$path_translated = 'PATH_TRANSLATED $document_root$fastcgi_path_info'
$script_filename = 'SCRIPT_FILENAME $document_root$fastcgi_script_name'

nginx::resource::vhost { 'greenspace.dev.pedrocandeias.com':
  ensure       => present,
  server_name  => [$vhost],
  listen_port  => 80,
  index_files  => undef,
  www_root     => $www_root,
  try_files           => ['$uri', '@rewriteapp'],
}

nginx::resource::location { 'greenspace.dev.pedrocandeias.com-php-1-rewrite':
  ensure              => 'present',
  vhost               => $vhost,
  www_root            => $www_root,
  location            => '@rewriteapp',
  location_cfg_append => {
    'rewrite'             => '^(.*)$ /app.php/$1 last'
  }
}

nginx::resource::location { 'greenspace.dev.pedrocandeias.com-php-2-regex':
  ensure              => 'present',
  vhost               => $vhost,
  www_root            => $www_root,
  location            => '~ ^/(app|app_dev|config)\.php(/|$)',
  location_cfg_append => {
    'fastcgi_pass'              => 'unix:/var/run/php5-fpm.sock',
    'fastcgi_split_path_info '  => '^(.+\.php)(/.*)$',
    'include '                  => 'fastcgi_params',
    'fastcgi_param '            => 'SCRIPT_FILENAME $document_root$fastcgi_script_name',
    'fastcgi_param  '           => 'HTTPS off'
  }
}


class { 'php':
  package             => 'php5-fpm',
  service             => 'php5-fpm',
  service_autorestart => false,
  config_file         => '/etc/php5/fpm/php.ini',
  module_prefix       => ''
}

php::module {
  [
    'php5-mysql',
    'php5-cli',
    'php5-curl',
    'php5-intl',
    'php5-mcrypt',
    'php5-apcu'
  ]:
  service => 'php5-fpm',
}

service { 'php5-fpm':
  ensure     => running,
  enable     => true,
  hasrestart => true,
  hasstatus  => true,
  require    => Package['php5-fpm'],
}

class { 'php::devel':
  require => Class['php'],
}

class { 'php::pear':
  require => Class['php'],
}



php::pecl::module { 'xhprof':
  use_package     => false,
  preferred_state => 'beta',
}

nginx::resource::vhost { 'xhprof':
  ensure      => present,
  server_name => ['xhprof'],
  listen_port => 80,
  index_files => ['index.php'],
  www_root    => '/var/www/xhprof/xhprof_html',
  try_files   => ['$uri', '$uri/', '/index.php?$args'],
  require     => Php::Pecl::Module['xhprof']
}


class { 'xdebug':
  service => 'nginx',
}

class { 'composer':
  require => Package['php5-fpm', 'curl'],
}

puphpet::ini { 'xdebug':
  value   => [
    'xdebug.default_enable = 1',
    'xdebug.remote_autostart = 0',
    'xdebug.remote_connect_back = 1',
    'xdebug.remote_enable = 1',
    'xdebug.remote_handler = "dbgp"',
    'xdebug.remote_port = 9000'
  ],
  ini     => '/etc/php5/fpm/conf.d/zzz_xdebug.ini',
  notify  => Service['php5-fpm'],
  require => Class['php'],
}

puphpet::ini { 'php':
  value   => [
    'date.timezone = "UTC"'
  ],
  ini     => '/etc/php5/fpm/conf.d/zzz_php.ini',
  notify  => Service['php5-fpm'],
  require => Class['php'],
}

puphpet::ini { 'custom':
  value   => [
    'display_errors = On',
    'error_reporting = -1',
    'allow_url_fopen = 1'
  ],
  ini     => '/etc/php5/fpm/conf.d/zzz_custom.ini',
  notify  => Service['php5-fpm'],
  require => Class['php'],
}


class { 'mysql::server':
  config_hash   => { 'root_password' => 'vagrantpassword' }
}

mysql::db { 'greenspace':
  grant    => [
    'ALL'
  ],
  user     => 'dev',
  password => 'dev',
  host     => 'localhost',
  charset  => 'utf8',
  require  => Class['mysql::server'],
}
