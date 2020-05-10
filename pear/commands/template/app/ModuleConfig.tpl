# master.yml
#
#Licensed under the Apache License, Version 2.0 (the "License"),
#see LICENSE for more details: http://www.apache.org/licenses/LICENSE-2.0.
#
# @author   Zhang Yi <loeyae@gmail.com>
# @version  <{$smarty.now|date_format: "%Y-%m-%d %H:%M:%S"}>
#
- settings: [master] # Required
  module:
    module_id: home # Required
    plugin:               # Required
        -
            name: \loeye\plugin\TranslatorPlugin
    view:
        default:
            tpl: home.tpl