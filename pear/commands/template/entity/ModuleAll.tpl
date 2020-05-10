      - name: \loeye\plugin\JWTPlugin
        format: json
        out: login_info
      - name: \loeye\plugin\CheckRequestMethodPlugin
        allowed: GET
      - name: \loeye\plugin\BuildQueryPlugin
        type: 1
        prefix: <{$pluginName}>
        validate: <{$entityFullName}>
      - name: \loeye\plugin\OutputPlugin
        if: __lyHasError[BuildQueryPlugin_errors]
        validate_error: BuildQueryPlugin_errors
      - name: <{$pluginFullName}>
      - name: \loeye\plugin\OutputPlugin
        output_data: <{$pluginName}>_output
        error: <{$pluginName}>_errors