- settings: [master]
  routes:
    home:
        path: ^/$
        module_id: home

    page:
        path : ^/{module}/$
        module_id : '{module}'
        regex:
            module: \w+
    action:
        path: ^/{module}/{action}$
        module_id: '{module}.{action}'
        regex:
          module: \w+
          action: \w+
    action_resource:
      path: ^/{module}/{action}/{id}$
      module_id: '{module}.{action}'
      regex:
        module: \w+
        action: \w+
        id: \d+