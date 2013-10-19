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

    apt::key { '4F4EA0AAE5267A6C': }

apt::ppa { 'ppa:ondrej/php5-oldstable':
  require => Apt::Key['4F4EA0AAE5267A6C']
}

class { 'puphpet::dotfiles': }

package { [
    'build-essential',
    'vim',
    'curl',
    'git-core',
    'optipng',
    'jpegoptim'
  ]:
  ensure  => 'installed',
}

class { 'nginx': }


nginx::resource::vhost { 'greenspace.dev':
  ensure       => present,
  server_name  => [
    'greenspace.dev'
  ],
  listen_port  => 80,
  index_files  => ['app.php'],
  www_root     => '/server/greenspace/frontend/web',
  try_files    => ['$uri', '$uri/', '/app.php?$args'],
}

$path_translated = 'PATH_TRANSLATED $document_root$fastcgi_path_info'
$script_filename = 'SCRIPT_FILENAME $document_root$fastcgi_script_name'

nginx::resource::location { 'greenspace.dev-php':
  ensure              => 'present',
  vhost               => 'greenspace.dev',
  location            => '~ \.php$',
  proxy               => undef,
  index_files         => ['app.php'],
  try_files           => ['$uri', '$uri/', '/app.php?$args'],
  www_root            => '/server/greenspace/frontend/web',
  location_cfg_append => {
    'fastcgi_split_path_info' => '^(.+\.php)(/.+)$',
    'fastcgi_param'           => 'PATH_INFO $fastcgi_path_info',
    'fastcgi_param '          => $path_translated,
    'fastcgi_param  '         => $script_filename,
    'fastcgi_param   '           => 'APP_ENV dev',
    'fastcgi_param    '           => 'APP_DEBUG true',
    'fastcgi_pass'            => 'unix:/var/run/php5-fpm.sock',
    'fastcgi_index'           => 'app.php',
    'include'                 => 'fastcgi_params'
  },
  notify              => Class['nginx::service'],
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
    'php5-sqlite',
    'php5-cli',
    'php5-curl',
    'php5-intl',
    'php5-mcrypt',
    'php5-gd'
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



class { 'composer':
  require => Package['php5-fpm', 'curl'],
}

puphpet::ini { 'php':
  value   => [
    'date.timezone = "UTC"'
  ],
  ini     => '/etc/php5/conf.d/zzz_php.ini',
  notify  => Service['php5-fpm'],
  require => Class['php'],
}

puphpet::ini { 'custom':
  value   => [
    'display_errors = On',
    'error_reporting = -1',
    'short_open_tag = Off',
    'intl.default_locale = en',
  ],
  ini     => '/etc/php5/conf.d/zzz_custom.ini',
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
