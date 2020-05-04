- settings: [master] # Required
  module:
    module_id: home # Required
    plugin:               # Required
        -
            name: \loeye\plugin\TranslatorPlugin
    view:
        default:
            tpl: home.tpl