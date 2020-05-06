- settings: [master]
  server:
      port: 80
      dispatcher: service
      rewrite:
        '^/$': 'index'
        '^/<handler:\w+>.html': '{handler}'
        '^/<service:\w+>/<handler:\w+>.html': '{service}/{handler}'
        '^/<module:\w+>/<service:\w+>/<handler:\w+>.html': '{module}/{service}/{handler}'
        '^/<service:\w+>/<handler:\w+>\<id:\d+>.html': '{service}/{handler}'
        '^/<module:\w+>/<service:\w+>/<handler:\w+>\<id:\d+>.html': '{module}/{service}/{handler}'
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
