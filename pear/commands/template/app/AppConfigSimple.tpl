- settings: [master]
  server:
      port: 80
      dispatcher: simple
      rewrite:
        '^/$': 'index/index'
        '^/<controller:\w+>.html': '{controller}/index'
        '^/<controller:\w+>/<action:\w+>.html': '{controller}/{action}'
        '^/<module:\w+>/<controller:\w+>/<action:\w+>.html': '{module}/{controller}/{action}'
        '^/<controller:\w+>/<action:\w+>/<id:\d+>.html': '{controller}/{action}'
        '^/<module:\w+>/<controller:\w+>/<action:\w+>/<id:\d+>.html': '{module}/{controller}/{action}'
      static_path:
        - ../htdocs
        - ../vendor/twbs/bootstrap
  profile: ${LOEYAE_ACTIVE_PROFILE:local}
  constants:
        BASE_SERVER_URL: http://localhost
  application:
    cache: pfile # One of "apc"; "array"; "file"; "memcached"; "parray"; "pfile"; "redis"
    database:
        default: default
        is_dev_mode: true
        encrypt_mode: explicit # One of "explicit"; "crypt"; "keydb"
  configuration:
    timezone: Asia/Shanghai # Required
  locale:
    default: zh_CN
    basename: lang # Required
    supported_languages: ["zh_CN"]
