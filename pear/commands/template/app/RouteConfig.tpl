- settings: [master]
  routes:
    home:
        path: ^/$
        module_id: home

    page:
        path : ^/{module}/$
        module_id : {module}
        regex:
            module: \w+